<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptGraded;
use App\Models\Cpt;
use App\Models\User;
use App\Services\VatEudService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GradeCpt
{
    public function __construct(
        private readonly VatEudService $vatEud,
    ) {}

    public function execute(Cpt $cpt, bool $passed, User $grader): void
    {
        $cpt->update(['passed' => $passed]);
        $cpt->load('course', 'trainee', 'examiner', 'logs');

        if ($cpt->log_uploaded && $cpt->logs->isNotEmpty()) {
            $this->syncWithVatEud($cpt, $passed);
        }

        event(new CptGraded($cpt, $passed, $grader));
    }

    private function syncWithVatEud(Cpt $cpt, bool $passed): void
    {
        $latestLog = $cpt->logs->sortByDesc('created_at')->first();
        $filePath  = $this->resolveFilePath($latestLog->log_file);

        if (!$filePath) {
            Log::error('CPT grading: log file not found', [
                'cpt_id' => $cpt->id,
                'log_id' => $latestLog->id,
                'log_file' => $latestLog->log_file,
            ]);
            return;
        }

        if (!$cpt->examiner) {
            Log::error('CPT grading: no examiner assigned', ['cpt_id' => $cpt->id]);
            return;
        }

        $uploadResult = $this->vatEud->uploadCptLog(
            traineeCid:  $cpt->trainee->vatsim_id,
            examinerCid: $cpt->examiner->vatsim_id,
            position:    $cpt->course->solo_station,
            note:        'See log',
            cptPass:     $passed,
            filePath:    $filePath,
        );

        if (!$uploadResult['success']) {
            Log::error('CPT grading: VatEud upload failed', ['cpt_id' => $cpt->id, 'result' => $uploadResult]);
            return;
        }

        if ($passed) {
            $this->requestUpgrade($cpt);
        }
    }

    private function requestUpgrade(Cpt $cpt): void
    {
        $newRating    = $cpt->trainee->rating + 1;
        $upgradeResult = $this->vatEud->requestUpgrade(
            traineeCid:   $cpt->trainee->vatsim_id,
            instructorCid: config('services.vateud.atd_lead_cid', 1441619),
            newRating:    $newRating,
        );

        if (!$upgradeResult['success']) {
            Log::error('CPT grading: upgrade request failed', ['cpt_id' => $cpt->id, 'result' => $upgradeResult]);
            return;
        }

        $cpt->trainee->update([
            'rating_upgraded_at' => now(),
            'last_known_rating'  => $cpt->trainee->rating,
        ]);
    }

    private function resolveFilePath(string $logFile): ?string
    {
        if (Storage::disk('private')->exists($logFile)) {
            return Storage::disk('private')->path($logFile);
        }

        if (Storage::disk('public')->exists($logFile)) {
            return Storage::disk('public')->path($logFile);
        }

        return null;
    }
}