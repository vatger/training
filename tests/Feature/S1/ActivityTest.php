<?php

namespace Tests\Feature\S1;

use App\Models\User;
use App\Models\S1\S1Module;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1WaitingList;
use App\Services\MoodleService;
use App\Services\S1\S1ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Mockery;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected S1ActivityService $activityService;
    protected MoodleService $moodleService;
    protected User $user;
    protected S1Module $module1;
    protected S1Module $module2;
    protected S1Module $module3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->moodleService = Mockery::mock(MoodleService::class);
        $this->app->instance(MoodleService::class, $this->moodleService);
        
        $this->activityService = app(S1ActivityService::class);
        
        $this->user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
        
        $this->module1 = S1Module::factory()->create([
            'sequence_order' => 1,
            'name' => 'Module 1',
        ]);
        
        $this->module2 = S1Module::factory()->create([
            'sequence_order' => 2,
            'name' => 'Module 2',
            'moodle_quiz_ids' => [1526, 1527, 1525, 1528],
        ]);
        
        $this->module3 = S1Module::factory()->create([
            'sequence_order' => 3,
            'name' => 'Module 3',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_check_module_2_activity_for_user_with_no_quizzes()
    {
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'completed_at' => now()->subDays(10),
        ]);

        $this->moodleService->shouldReceive('getActivityCompletion')
            ->times(4)
            ->andReturn(false);

        [$success, $message, $status] = $this->activityService->checkModule2Activity($this->user);

        $this->assertTrue($success);
        $this->assertEquals('in_progress', $status['status']);
        $this->assertEquals(0, $status['completed_quizzes']);
        $this->assertEquals(4, $status['total_quizzes']);
        $this->assertFalse($status['is_inactive']);
    }

    public function test_check_module_2_activity_marks_inactive_after_40_days()
    {
        Carbon::setTestNow('2024-01-01');
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'completed_at' => Carbon::parse('2023-11-10'),
        ]);

        $this->moodleService->shouldReceive('getActivityCompletion')
            ->times(4)
            ->andReturn(false);

        [$success, $message, $status] = $this->activityService->checkModule2Activity($this->user);

        $this->assertTrue($success);
        $this->assertTrue($status['is_inactive']);
        $this->assertTrue($status['days_since_last_activity'] > 40);
        
        Carbon::setTestNow();
    }

    public function test_check_module_2_activity_with_all_quizzes_completed()
    {
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'completed_at' => now()->subDays(10),
        ]);

        $this->moodleService->shouldReceive('getActivityCompletion')
            ->times(4)
            ->andReturn(true);

        [$success, $message, $status] = $this->activityService->checkModule2Activity($this->user);

        $this->assertTrue($success);
        $this->assertEquals(4, $status['completed_quizzes']);
        $this->assertEquals(4, $status['total_quizzes']);
        $this->assertFalse($status['is_inactive']);
    }

    public function test_check_next_module_signup_deadline()
    {
        Carbon::setTestNow('2024-01-01');
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module2->id,
            'completed_at' => Carbon::parse('2023-12-01'),
        ]);

        [$success, $message, $status] = $this->activityService->checkNextModuleSignupDeadline($this->user, 2);

        $this->assertTrue($success);
        $this->assertEquals('deadline_active', $status['status']);
        $this->assertEquals(31, $status['days_since_completion']);
        $this->assertFalse($status['deadline_passed']);
        $this->assertTrue($status['needs_warning']);
        
        Carbon::setTestNow();
    }

    public function test_next_module_signup_deadline_passed()
    {
        Carbon::setTestNow('2024-02-15');
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module2->id,
            'completed_at' => Carbon::parse('2024-01-01'),
        ]);

        [$success, $message, $status] = $this->activityService->checkNextModuleSignupDeadline($this->user, 2);

        $this->assertTrue($success);
        $this->assertTrue($status['deadline_passed']);
        $this->assertEquals(45, $status['days_since_completion']);
        
        Carbon::setTestNow();
    }

    public function test_user_already_signed_up_for_next_module()
    {
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module2->id,
            'completed_at' => now()->subDays(10),
        ]);
        
        S1WaitingList::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module3->id,
            'is_active' => true,
        ]);

        [$success, $message, $status] = $this->activityService->checkNextModuleSignupDeadline($this->user, 2);

        $this->assertTrue($success);
        $this->assertEquals('signed_up', $status['status']);
    }

    public function test_get_user_activity_status_with_confirmation_needed()
    {
        S1WaitingList::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'is_active' => true,
            'confirmation_due_at' => now()->subDay(),
        ]);

        $statuses = $this->activityService->getUserActivityStatus($this->user);

        $this->assertCount(1, $statuses);
        $this->assertEquals('confirmation_overdue', $statuses[0]['warning']);
        $this->assertTrue($statuses[0]['action_required']);
    }

    public function test_get_user_activity_status_with_approaching_expiry()
    {
        S1WaitingList::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'is_active' => true,
            'expires_at' => now()->addDays(5),
        ]);

        $statuses = $this->activityService->getUserActivityStatus($this->user);

        $this->assertCount(1, $statuses);
        $this->assertEquals('expiry_approaching', $statuses[0]['warning']);
        $this->assertTrue($statuses[0]['action_required']);
        $this->assertEquals(5, $statuses[0]['days_remaining']);
    }

    public function test_mark_module_2_inactive()
    {
        S1WaitingList::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module2->id,
            'is_active' => true,
        ]);

        $result = $this->activityService->markModule2Inactive($this->user, 'No activity for 40 days');

        $this->assertTrue($result);
        
        $waitingList = S1WaitingList::where('user_id', $this->user->id)
            ->where('module_id', $this->module2->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
    }

    public function test_check_module_2_activity_returns_error_for_non_module_1_completion()
    {
        [$success, $message, $status] = $this->activityService->checkModule2Activity($this->user);

        $this->assertFalse($success);
        $this->assertStringContainsString('Module 1 not completed', $message);
    }

    public function test_check_module_2_already_completed()
    {
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'completed_at' => now()->subDays(10),
        ]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module2->id,
            'completed_at' => now()->subDays(5),
        ]);

        $this->moodleService->shouldNotReceive('getActivityCompletion');

        [$success, $message, $status] = $this->activityService->checkModule2Activity($this->user);

        $this->assertTrue($success);
        $this->assertEquals('completed', $status['status']);
    }

    public function test_activity_status_includes_multiple_warnings()
    {
        S1WaitingList::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'is_active' => true,
            'confirmation_due_at' => now()->subDay(),
            'expires_at' => now()->addDays(5),
        ]);

        $statuses = $this->activityService->getUserActivityStatus($this->user);

        $this->assertCount(1, $statuses);
        $this->assertTrue(isset($statuses[0]['warning']));
        $this->assertTrue($statuses[0]['action_required']);
    }

    public function test_module_2_warning_appears_before_inactive()
    {
        Carbon::setTestNow('2024-02-01');
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
            'completed_at' => Carbon::parse('2024-01-01'),
        ]);

        $this->moodleService->shouldReceive('getActivityCompletion')
            ->times(4)
            ->andReturn(false);

        $statuses = $this->activityService->getUserActivityStatus($this->user);

        $module2Warning = collect(value: $statuses)->firstWhere('type', 'module2_activity');
        
        $this->assertNotNull($module2Warning);
        $this->assertEquals('inactivity_warning', $module2Warning['warning']);
        
        Carbon::setTestNow();
    }
}