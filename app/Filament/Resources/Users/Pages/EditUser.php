<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasUnlockableFields;
use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Models\ChiefOfTraining;
use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\LeadingMentor;
use App\Models\WaitingListEntry;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    use HasUnlockableFields;

    protected static string $resource = UserResource::class;

    /** @var array<int, string> */
    protected array $originalRoleNames = [];

    /** @var array<int, string> */
    protected array $originalPermissionNames = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 'permissions' relationship is synced automatically by Filament via CheckboxList::relationship()
        unset($data['permissions']);

        $record->update($data);

        return $record;
    }

    protected function beforeSave(): void
    {
        $this->originalRoleNames = $this->record->roles()->pluck('name')->sort()->values()->all();
        $this->originalPermissionNames = $this->record->permissions()->pluck('name')->sort()->values()->all();
    }

    protected function afterSave(): void
    {
        $newRoleNames = $this->record->roles()->pluck('name')->sort()->values()->all();
        $newPermissionNames = $this->record->permissions()->pluck('name')->sort()->values()->all();

        if ($newRoleNames !== $this->originalRoleNames) {
            ActivityLog::record(
                'user.roles_updated',
                "{$this->getAdminName()} updated roles for {$this->record->name}",
                $this->record,
                [
                    'added' => array_values(array_diff($newRoleNames, $this->originalRoleNames)),
                    'removed' => array_values(array_diff($this->originalRoleNames, $newRoleNames)),
                ],
            );
        }

        if ($newPermissionNames !== $this->originalPermissionNames) {
            ActivityLog::record(
                'user.permissions_updated',
                "{$this->getAdminName()} updated direct permissions for {$this->record->name}",
                $this->record,
                [
                    'added' => array_values(array_diff($newPermissionNames, $this->originalPermissionNames)),
                    'removed' => array_values(array_diff($this->originalPermissionNames, $newPermissionNames)),
                ],
            );
        }
    }

    protected function getAdminName(): string
    {
        return auth()->user()?->name ?? 'System';
    }

    public function addCourseEnrollment(int $courseId): void
    {
        if ($this->record->activeCourses()->where('course_id', $courseId)->exists()) {
            Notification::make()->title('Already enrolled in this course')->warning()->send();

            return;
        }

        $this->record->activeCourses()->attach($courseId);

        $course = Course::find($courseId);
        ActivityLog::record(
            'user.course_enrollment_added',
            "{$this->getAdminName()} enrolled {$this->record->name} in {$course?->name}",
            $this->record,
            ['course_id' => $courseId, 'course_name' => $course?->name],
        );

        Notification::make()->title('Course enrollment added')->success()->send();
    }

    public function removeCourseEnrollment(int $courseId): void
    {
        $this->record->activeCourses()->detach($courseId);

        $course = Course::find($courseId);
        ActivityLog::record(
            'user.course_enrollment_removed',
            "{$this->getAdminName()} removed {$this->record->name} from {$course?->name}",
            $this->record,
            ['course_id' => $courseId, 'course_name' => $course?->name],
        );

        Notification::make()->title('Course enrollment removed')->success()->send();
    }

    public function updateCourseEnrollmentPivot(int $courseId, array $data): void
    {
        $this->record->activeCourses()->updateExistingPivot($courseId, [
            'claimed_by_mentor_id' => $data['claimed_by_mentor_id'] ?? null,
            'claimed_at' => $data['claimed_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        $course = Course::find($courseId);
        ActivityLog::record(
            'user.course_enrollment_updated',
            "{$this->getAdminName()} updated {$this->record->name}'s enrollment in {$course?->name}",
            $this->record,
            ['course_id' => $courseId, 'course_name' => $course?->name, 'changes' => $data],
        );

        Notification::make()->title('Course enrollment updated')->success()->send();
    }

    public function addWaitingListEntry(int $courseId): void
    {
        if ($this->record->waitingListEntries()->where('course_id', $courseId)->exists()) {
            Notification::make()->title('Already on the waiting list for this course')->warning()->send();

            return;
        }

        WaitingListEntry::create(['user_id' => $this->record->id, 'course_id' => $courseId]);
        Notification::make()->title('Added to waiting list')->success()->send();
    }

    public function removeWaitingListEntry(int $waitingListEntryId): void
    {
        WaitingListEntry::where('id', $waitingListEntryId)->where('user_id', $this->record->id)->delete();
        Notification::make()->title('Removed from waiting list')->success()->send();
    }

    public function updateWaitingListEntry(int $waitingListEntryId, array $data): void
    {
        WaitingListEntry::where('id', $waitingListEntryId)->where('user_id', $this->record->id)->update([
            'activity' => $data['activity'] ?? 0,
            'remarks' => $data['remarks'] ?? null,
        ]);
        Notification::make()->title('Waiting list entry updated')->success()->send();
    }

    public function addFamiliarisation(int $sectorId): void
    {
        if ($this->record->familiarisations()->where('familiarisation_sector_id', $sectorId)->exists()) {
            Notification::make()->title('Already has this familiarisation')->warning()->send();

            return;
        }

        Familiarisation::create(['user_id' => $this->record->id, 'familiarisation_sector_id' => $sectorId]);
        Notification::make()->title('Familiarisation added')->success()->send();
    }

    public function removeFamiliarisation(int $familiarisationId): void
    {
        Familiarisation::where('id', $familiarisationId)->where('user_id', $this->record->id)->delete();
        Notification::make()->title('Familiarisation removed')->success()->send();
    }

    public function addChiefOfTrainingCourse(int $courseId): void
    {
        if (ChiefOfTraining::where('user_id', $this->record->id)->where('course_id', $courseId)->exists()) {
            Notification::make()->title('Already Chief of Training for this course')->warning()->send();

            return;
        }

        ChiefOfTraining::create(['user_id' => $this->record->id, 'course_id' => $courseId]);
        Notification::make()->title('Chief of Training added')->success()->send();
    }

    public function removeChiefOfTrainingCourse(int $chiefOfTrainingId): void
    {
        ChiefOfTraining::where('id', $chiefOfTrainingId)->where('user_id', $this->record->id)->delete();
        Notification::make()->title('Chief of Training removed')->success()->send();
    }

    public function addLeadingMentorFir(string $fir): void
    {
        if (LeadingMentor::where('user_id', $this->record->id)->where('fir', $fir)->exists()) {
            Notification::make()->title('Already Leading Mentor for this FIR')->warning()->send();

            return;
        }

        LeadingMentor::create(['user_id' => $this->record->id, 'fir' => $fir]);
        Notification::make()->title('Leading Mentor FIR added')->success()->send();
    }

    public function removeLeadingMentorFir(int $leadingMentorId): void
    {
        LeadingMentor::where('id', $leadingMentorId)->where('user_id', $this->record->id)->delete();
        Notification::make()->title('Leading Mentor FIR removed')->success()->send();
    }
}
