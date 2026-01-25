<?php

namespace Tests\Feature\S1;

use App\Jobs\S1\SelectSessionParticipants;
use App\Models\User;
use App\Models\S1\S1Module;
use App\Models\S1\S1Session;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1WaitingList;
use App\Services\S1\S1SessionService;
use App\Services\S1\S1WaitingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Carbon\Carbon;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    protected S1SessionService $sessionService;
    protected S1WaitingListService $waitingListService;
    protected User $mentor;
    protected S1Module $module1;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sessionService = app(S1SessionService::class);
        $this->waitingListService = app(S1WaitingListService::class);
        
        $this->mentor = User::factory()->create(['is_staff' => true]);
        $this->module1 = S1Module::factory()->create(['sequence_order' => 1, 'name' => 'Module 1']);
    }

    public function test_mentor_can_create_session()
    {
        $scheduledAt = now()->addDays(7);

        [$success, $message, $session] = $this->sessionService->createSession(
            $this->module1->id,
            $this->mentor->id,
            $scheduledAt,
            15,
            'DE',
            'Test session notes'
        );

        $this->assertTrue($success);
        $this->assertInstanceOf(S1Session::class, $session);
        $this->assertEquals($this->module1->id, $session->module_id);
        $this->assertEquals($this->mentor->id, $session->mentor_id);
        $this->assertTrue($session->signups_open);
        $this->assertFalse($session->signups_locked);
        $this->assertEquals($scheduledAt->subHours(48)->format('Y-m-d H:i'), $session->signups_lock_at->format('Y-m-d H:i'));
    }

    public function test_user_can_signup_for_session()
    {
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
            'scheduled_at' => now()->addDays(7),
            'signups_open' => true,
            'signups_locked' => false,
        ]);

        [$success, $message] = $this->sessionService->signupForSession($session, $user);

        $this->assertTrue($success);
        
        $signup = S1SessionSignup::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();
            
        $this->assertNotNull($signup);
        $this->assertFalse($signup->was_selected);
    }

    public function test_user_cannot_signup_without_waiting_list_entry()
    {
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
            'scheduled_at' => now()->addDays(7),
        ]);

        [$success, $message] = $this->sessionService->signupForSession($session, $user);

        $this->assertFalse($success);
        $this->assertStringContainsString('waiting list', $message);
    }

    public function test_user_cannot_signup_when_signups_locked()
    {
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
            'scheduled_at' => now()->addDays(1),
            'signups_locked' => true,
        ]);

        [$success, $message] = $this->sessionService->signupForSession($session, $user);

        $this->assertFalse($success);
        $this->assertStringContainsString('locked', $message);
    }

    public function test_user_can_cancel_signup()
    {
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
            'scheduled_at' => now()->addDays(7),
        ]);

        $this->sessionService->signupForSession($session, $user);
        
        [$success, $message] = $this->sessionService->cancelSignup($session, $user);

        $this->assertTrue($success);
        
        $signup = S1SessionSignup::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();
            
        $this->assertNull($signup);
    }

    public function test_user_cannot_signup_for_multiple_sessions_same_module()
    {
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $session1 = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'scheduled_at' => now()->addDays(7),
        ]);
        
        $session2 = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'scheduled_at' => now()->addDays(10),
        ]);

        $this->sessionService->signupForSession($session1, $user);
        
        [$success, $message] = $this->sessionService->signupForSession($session2, $user);

        $this->assertFalse($success);
        $this->assertStringContainsString('only sign up for one session', $message);
    }

    public function test_participants_selected_based_on_waiting_list_order()
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create([
                'vatsim_id' => 1000000 + $i,
                'subdivision' => 'GER',
            ]);
            
            Carbon::setTestNow(now()->addMinutes($i));
            $this->waitingListService->joinWaitingList($user, $this->module1);
            
            $users[] = $user;
        }
        
        Carbon::setTestNow();
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'mentor_id' => $this->mentor->id,
            'scheduled_at' => now()->addDays(7),
            'max_trainees' => 3,
            'signups_locked' => false,
        ]);

        foreach ($users as $user) {
            $this->sessionService->signupForSession($session, $user);
        }
        
        $session->update(['signups_locked' => true]);
        
        [$success, $message, $data] = $this->sessionService->selectParticipants($session);

        $this->assertTrue($success);
        $this->assertEquals(3, $data['selected']);
        $this->assertEquals(2, $data['rejected']);
        
        $selectedSignups = S1SessionSignup::where('session_id', $session->id)
            ->where('was_selected', true)
            ->get();
            
        $this->assertCount(3, $selectedSignups);
        
        $this->assertTrue($selectedSignups->pluck('user_id')->contains($users[0]->id));
        $this->assertTrue($selectedSignups->pluck('user_id')->contains($users[1]->id));
        $this->assertTrue($selectedSignups->pluck('user_id')->contains($users[2]->id));
        $this->assertFalse($selectedSignups->pluck('user_id')->contains($users[3]->id));
        $this->assertFalse($selectedSignups->pluck('user_id')->contains($users[4]->id));
    }

    public function test_session_locks_48_hours_before_scheduled_time()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'scheduled_at' => Carbon::parse('2024-01-03 12:00:00'),
            'signups_locked' => false,
        ]);

        $this->assertFalse($session->shouldLockSignups());
        
        Carbon::setTestNow('2024-01-01 12:01:00');
        
        $session->refresh();
        $this->assertTrue($session->shouldLockSignups());
        
        Carbon::setTestNow();
    }

    public function test_session_capacity_tracking()
    {
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'max_trainees' => 10,
        ]);

        $users = User::factory()->count(7)->create(['subdivision' => 'GER']);
        
        foreach ($users as $user) {
            $this->waitingListService->joinWaitingList($user, $this->module1);
            S1SessionSignup::factory()->create([
                'session_id' => $session->id,
                'user_id' => $user->id,
                'was_selected' => true,
            ]);
        }

        $this->assertEquals(3, $session->available_spots);
        $this->assertEquals(7, $session->total_signups);
    }

    public function test_user_cannot_cancel_after_selection()
    {
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        $this->waitingListService->joinWaitingList($user, $this->module1);
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'scheduled_at' => now()->addDays(7),
            'signups_locked' => true,
        ]);

        $signup = S1SessionSignup::factory()->create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'was_selected' => true,
        ]);

        [$success, $message] = $this->sessionService->cancelSignup($session, $user);

        $this->assertFalse($success);
        $this->assertStringContainsString('selected', $message);
    }

    public function test_non_selected_signups_are_deleted_after_selection()
    {
        Queue::fake();
        
        $selectedUser = User::factory()->create(['subdivision' => 'GER']);
        $rejectedUser = User::factory()->create(['subdivision' => 'GER']);
        
        $this->waitingListService->joinWaitingList($selectedUser, $this->module1);
        $this->waitingListService->joinWaitingList($rejectedUser, $this->module1);
        
        $session = S1Session::factory()->create([
            'module_id' => $this->module1->id,
            'max_trainees' => 1,
            'signups_locked' => true,
        ]);

        S1SessionSignup::factory()->create([
            'session_id' => $session->id,
            'user_id' => $selectedUser->id,
        ]);
        
        S1SessionSignup::factory()->create([
            'session_id' => $session->id,
            'user_id' => $rejectedUser->id,
        ]);

        SelectSessionParticipants::dispatch($session);
        
        Queue::assertPushed(SelectSessionParticipants::class);
    }
}