<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\ChiefOfTraining;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
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

    public function addMentor(int $userId): void
    {
        if ($this->record->mentors()->where('user_id', $userId)->exists()) {
            Notification::make()->title('Already a mentor for this course')->warning()->send();

            return;
        }

        $this->record->mentors()->attach($userId);
        Notification::make()->title('Mentor added')->success()->send();
    }

    public function removeMentor(int $userId): void
    {
        if ($this->record->mentors()->count() <= 1) {
            Notification::make()->title('Cannot remove the last mentor')->danger()->send();

            return;
        }

        $this->record->mentors()->detach($userId);
        Notification::make()->title('Mentor removed')->success()->send();
    }

    public function addTrainee(int $userId): void
    {
        if ($this->record->allTrainees()->where('user_id', $userId)->exists()) {
            Notification::make()->title('Already a trainee on this course')->warning()->send();

            return;
        }

        $this->record->allTrainees()->attach($userId);
        Notification::make()->title('Trainee added')->success()->send();
    }

    public function removeTrainee(int $userId): void
    {
        $this->record->allTrainees()->detach($userId);
        Notification::make()->title('Trainee removed')->success()->send();
    }

    public function updateTraineePivot(int $userId, array $data): void
    {
        $this->record->allTrainees()->updateExistingPivot($userId, [
            'claimed_by_mentor_id' => $data['claimed_by_mentor_id'] ?? null,
            'claimed_at' => $data['claimed_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);
        Notification::make()->title('Trainee updated')->success()->send();
    }

    public function addChiefOfTraining(int $userId): void
    {
        if (ChiefOfTraining::where('course_id', $this->record->id)->where('user_id', $userId)->exists()) {
            Notification::make()->title('Already Chief of Training for this course')->warning()->send();

            return;
        }

        ChiefOfTraining::create(['course_id' => $this->record->id, 'user_id' => $userId]);
        Notification::make()->title('Chief of Training added')->success()->send();
    }

    public function removeChiefOfTraining(int $chiefOfTrainingId): void
    {
        ChiefOfTraining::find($chiefOfTrainingId)?->delete();
        Notification::make()->title('Chief of Training removed')->success()->send();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $endorsementGroups = \DB::table('course_endorsement_groups')
            ->where('course_id', $this->record->id)
            ->pluck('endorsement_group_name')
            ->toArray();

        $data['endorsement_groups'] = $endorsementGroups;

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
