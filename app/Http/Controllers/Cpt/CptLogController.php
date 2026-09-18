<?php

namespace App\Http\Controllers\Cpt;

use App\Domain\Cpt\Actions\UploadCptLog;
use App\Http\Controllers\Controller;
use App\Models\Cpt;
use App\Models\CptLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CptLogController extends Controller
{
    public function __construct(
        private readonly UploadCptLog $uploadCptLog,
    ) {}

    public function uploadPage(Cpt $cpt)
    {
        $user = auth()->user();
        $cpt->load(['trainee', 'examiner', 'local', 'course', 'logs.uploadedBy']);

        $canAccess = $user->isSuperuser()
            || $user->isLeadership()
            || $cpt->examiner_id === $user->id
            || $cpt->local_id === $user->id;

        if (! $canAccess) {
            return redirect()->route('cpt.index')
                ->withErrors(['error' => 'You do not have permission to view logs for this CPT.']);
        }

        $canUpload = $user->isSuperuser()
            || $cpt->examiner_id === $user->id
            || $cpt->local_id === $user->id;

        return Inertia::render('cpt/upload', [
            'cpt' => [
                'id' => $cpt->id,
                'trainee' => [
                    'id' => $cpt->trainee->id,
                    'name' => $cpt->trainee->full_name,
                    'vatsim_id' => $cpt->trainee->vatsim_id,
                ],
                'examiner' => $cpt->examiner ? [
                    'id' => $cpt->examiner->id,
                    'name' => $cpt->examiner->full_name,
                ] : null,
                'local' => $cpt->local ? [
                    'id' => $cpt->local->id,
                    'name' => $cpt->local->full_name,
                ] : null,
                'course' => [
                    'id' => $cpt->course->id,
                    'name' => $cpt->course->name,
                    'solo_station' => $cpt->course->solo_station,
                ],
                'date' => $cpt->date->toIso8601String(),
                'date_formatted' => $cpt->date->format('d M Y, H:i'),
                'confirmed' => $cpt->confirmed,
                'log_uploaded' => $cpt->log_uploaded,
            ],
            'logs' => $cpt->logs->map(fn ($log) => [
                'id' => $log->id,
                'file_name' => $log->file_name,
                'file_url' => $log->file_url,
                'uploaded_at' => $log->created_at->toIso8601String(),
                'uploaded_at_formatted' => $log->created_at->format('d M Y, H:i'),
                'uploaded_by' => [
                    'id' => $log->uploadedBy->id,
                    'name' => $log->uploadedBy->full_name,
                ],
            ]),
            'can_upload' => $canUpload,
            'can_review' => $user->isSuperuser() && $cpt->log_uploaded,
        ]);
    }

    public function upload(Request $request, Cpt $cpt)
    {
        $request->validate([
            'log_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $user = $request->user();

        if (! $user->isSuperuser() && $cpt->examiner_id !== $user->id && $cpt->local_id !== $user->id) {
            return back()->withErrors(['error' => 'You do not have permission to upload logs for this CPT.']);
        }

        $this->uploadCptLog->execute($cpt, $user, $request->file('log_file'));

        return back()->with('success', 'Log uploaded successfully.');
    }

    public function viewLog(CptLog $log)
    {
        $user = auth()->user();
        $cpt = $log->cpt()->first();

        $canView = $user->isSuperuser()
            || $user->isLeadership()
            || $cpt->examiner_id === $user->id
            || $cpt->local_id === $user->id;

        if (! $canView) {
            abort(403, 'Unauthorized access to CPT log.');
        }

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$log->file_name.'"',
        ];

        if (Storage::disk('private')->exists($log->log_file)) {
            return response()->file(Storage::disk('private')->path($log->log_file), $headers);
        }

        if (Storage::disk('public')->exists($log->log_file)) {
            return response()->file(Storage::disk('public')->path($log->log_file), $headers);
        }

        abort(404, 'File not found.');
    }
}
