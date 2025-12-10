<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $endorsementGroups = $data['endorsement_groups'] ?? [];
        unset($data['endorsement_groups']);

        $record = static::getModel()::create($data);

        if (is_array($endorsementGroups)) {
            foreach ($endorsementGroups as $groupName) {
                \DB::table('course_endorsement_groups')->insert([
                    'course_id' => $record->id,
                    'endorsement_group_name' => $groupName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $record;
    }
}