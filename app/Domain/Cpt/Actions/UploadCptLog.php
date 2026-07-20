<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptLogUploaded;
use App\Models\Cpt;
use App\Models\CptLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class UploadCptLog
{
    public function execute(Cpt $cpt, User $uploader, UploadedFile $file): CptLog
    {
        $path = $file->store('cpt_logs', 'private');

        $log = CptLog::create([
            'cpt_id' => $cpt->id,
            'uploaded_by_id' => $uploader->id,
            'log_file' => $path,
        ]);

        $cpt->update(['log_uploaded' => true]);

        event(new CptLogUploaded($log, $cpt, $uploader));

        return $log;
    }
}
