<?php

namespace Tests\Feature\S1;

use App\Models\User;
use App\Models\S1\S1Module;
use App\Models\S1\S1Session;
use App\Models\S1\S1Attendance;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1ModuleCompletion;
use App\Services\S1\S1AttendanceService;
use App\Services\S1\S1WaitingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected S1AttendanceService $attendanceService;
    protected S1WaitingListService $waitingListService;
    protected User $mentor;
    protected S1Module $module1;
    protected S1Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->attendanceService = app(S1AttendanceService::class);
        $this->waitingListService = app(S1WaitingListService::class);
        
        $this->mentor = User::factory()->create(['is_staff' => true]);
        $this->module1 = S1Module::factory()->create(['sequence_order' => 1, 'name' => 'Module 1']);
        
        $this->session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
            'scheduled_at' => now()->subHour(),
        ]);
    }

    public function test_mentor_can_mark_attendance_as_passed()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $signup = S1SessionSignup::factory()->create([
            'session_id' => $this->session->id,
            'user_id' => $user->id,
            'was_selected' => true,
        ]);

        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'passed',
            'Great performance',
            $this->mentor->id
        );

        $this->assertTrue($success);
        $this->assertInstanceOf(S1Attendance::class, $attendance);
        $this->assertEquals('passed', $attendance->status);
        $this->assertEquals('Great performance', $attendance->notes);
        
        $completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertNotNull($completion);
        
        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
    }

    public function test_absent_status_removes_user_from_waiting_list()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'absent',
            null,
            $this->mentor->id
        );

        $this->assertTrue($success);
        
        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
        
        $completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertNull($completion);
    }

    public function test_failed_status_removes_user_from_waiting_list()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'failed',
            'Did not meet requirements',
            $this->mentor->id
        );

        $this->assertTrue($success);
        
        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
        
        $completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertNull($completion);
    }

    public function test_excused_status_keeps_waiting_list_position()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'excused',
            'Valid excuse provided',
            $this->mentor->id
        );

        $this->assertTrue($success);
        
        $waitingList = S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertTrue($waitingList->is_active);
        
        $completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertNull($completion);
    }

    public function test_mentor_can_add_spontaneous_attendee()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);

        [$success, $message, $attendance] = $this->attendanceService->addSpontaneousAttendee(
            $this->session,
            $user,
            'passed',
            'Joined spontaneously',
            $this->mentor->id
        );

        $this->assertTrue($success);
        $this->assertTrue($attendance->spontaneous);
        
        $completion = S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertNotNull($completion);
    }

    public function test_cannot_add_spontaneous_attendee_without_waiting_list()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);

        [$success, $message, $attendance] = $this->attendanceService->addSpontaneousAttendee(
            $this->session,
            $user,
            'passed',
            null,
            $this->mentor->id
        );

        $this->assertFalse($success);
        $this->assertStringContainsString('waiting list', $message);
    }

    public function test_bulk_attendance_marking()
    {
        $users = User::factory()->count(3)->create(['subdivision' => 'GER']);
        
        foreach ($users as $user) {
            $this->waitingListService->joinWaitingList($user, $this->module1);
        }

        $attendanceData = [
            [
                'user_id' => $users[0]->id,
                'status' => 'passed',
                'notes' => 'Excellent',
            ],
            [
                'user_id' => $users[1]->id,
                'status' => 'passed',
                'notes' => 'Good',
            ],
            [
                'user_id' => $users[2]->id,
                'status' => 'failed',
                'notes' => 'Needs improvement',
            ],
        ];

        [$success, $message, $data] = $this->attendanceService->markAllAttendance(
            $this->session,
            $attendanceData,
            $this->mentor->id
        );

        $this->assertTrue($success);
        $this->assertEquals(3, $data['marked']);
        
        $this->session->refresh();
        $this->assertTrue($this->session->attendance_completed);
        
        $this->assertEquals(2, S1ModuleCompletion::where('module_id', $this->module1->id)->count());
    }

    public function test_attendance_can_be_updated()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'attended',
            'Initial mark',
            $this->mentor->id
        );
        
        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'passed',
            'Updated to passed',
            $this->mentor->id
        );

        $this->assertTrue($success);
        $this->assertEquals('passed', $attendance->status);
        $this->assertEquals('Updated to passed', $attendance->notes);
        
        $this->assertEquals(1, S1Attendance::where('user_id', $user->id)
            ->where('session_id', $this->session->id)
            ->count());
    }

    public function test_get_session_attendances()
    {
        $users = User::factory()->count(5)->create(['subdivision' => 'GER']);
        
        foreach ($users as $user) {
            $this->waitingListService->joinWaitingList($user, $this->module1);
            
            $this->attendanceService->markAttendance(
                $this->session,
                $user,
                'passed',
                null,
                $this->mentor->id
            );
        }

        $attendances = $this->attendanceService->getSessionAttendances($this->session);

        $this->assertCount(5, $attendances);
        $this->assertTrue($attendances->first()->relationLoaded('user'));
        $this->assertTrue($attendances->first()->relationLoaded('markedByMentor'));
    }

    public function test_get_user_attendance_history()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $sessions = S1Session::factory()->count(3)->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
        ]);
        
        foreach ($sessions as $session) {
            $this->attendanceService->markAttendance(
                $session,
                $user,
                'passed',
                null,
                $this->mentor->id
            );
        }

        $history = $this->attendanceService->getUserAttendanceHistory($user);

        $this->assertCount(3, $history);
        $this->assertTrue($history->first()->relationLoaded('session'));
    }

    public function test_invalid_attendance_status_is_rejected()
    {
        $user = User::factory()->create(['subdivision' => 'GER']);
        
        [$success, $message, $attendance] = $this->attendanceService->markAttendance(
            $this->session,
            $user,
            'invalid_status',
            null,
            $this->mentor->id
        );

        $this->assertFalse($success);
        $this->assertStringContainsString('Invalid', $message);
    }
}