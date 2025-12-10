<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['endorsement_groups'] = $this->record->endorsementGroups()->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $endorsementGroups = $data['endorsement_groups'] ?? [];
        unset($data['endorsement_groups']);

        $record->update($data);

        \DB::table('course_endorsement_groups')
            ->where('course_id', $record->id)
            ->delete();

        foreach ($endorsementGroups as $groupName) {
            \DB::table('course_endorsement_groups')->insert([
                'course_id' => $record->id,
                'endorsement_group_name' => $groupName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $record;
    }
}