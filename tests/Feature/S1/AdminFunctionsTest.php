<?php

namespace Tests\Feature\S1;

use App\Models\User;
use App\Models\S1\S1Module;
use App\Models\S1\S1UserBan;
use App\Models\S1\S1TraineeComment;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1ProgressReset;
use App\Models\S1\S1WaitingList;
use App\Services\S1\S1ProgressResetService;
use App\Services\S1\S1WaitingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminFunctionsTest extends TestCase
{
    use RefreshDatabase;

    protected S1ProgressResetService $progressResetService;
    protected S1WaitingListService $waitingListService;
    protected User $mentor;
    protected User $trainee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->progressResetService = app(S1ProgressResetService::class);
        $this->waitingListService = app(S1WaitingListService::class);
        
        $this->mentor = User::factory()->create(['is_staff' => true]);
        $this->trainee = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);
    }

    public function test_mentor_can_ban_user_temporarily()
    {
        $ban = S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Repeated no-shows',
            'banned_at' => now(),
            'expires_at' => now()->addDays(30),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(S1UserBan::class, $ban);
        $this->assertTrue($ban->is_active);
        $this->assertFalse($ban->isPermanent());
        $this->assertFalse($ban->isExpired());
    }

    public function test_mentor_can_ban_user_permanently()
    {
        $ban = S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Serious violation',
            'banned_at' => now(),
            'expires_at' => null,
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        $this->assertTrue($ban->isPermanent());
        $this->assertFalse($ban->isExpired());
    }

    public function test_ban_expires_after_expiry_date()
    {
        Carbon::setTestNow('2024-01-01');
        
        $ban = S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Test ban',
            'banned_at' => now(),
            'expires_at' => now()->addDays(1),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        $this->assertFalse($ban->isExpired());
        
        Carbon::setTestNow('2024-01-03');
        
        $this->assertTrue($ban->isExpired());
        
        Carbon::setTestNow();
    }

    public function test_banned_user_cannot_join_waiting_list()
    {
        S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Test ban',
            'banned_at' => now(),
            'expires_at' => now()->addDays(30),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        $module = S1Module::factory()->create(['sequence_order' => 1]);

        [$success, $message] = $this->waitingListService->joinWaitingList($this->trainee, $module);

        $this->assertFalse($success);
        $this->assertStringContainsString('banned', $message);
    }

    public function test_expired_ban_does_not_prevent_joining()
    {
        Carbon::setTestNow('2024-01-01');
        
        S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Old ban',
            'banned_at' => Carbon::parse('2023-12-01'),
            'expires_at' => Carbon::parse('2023-12-31'),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        $module = S1Module::factory()->create(['sequence_order' => 1]);

        [$success, $message, $waitingList] = $this->waitingListService->joinWaitingList($this->trainee, $module);

        $this->assertTrue($success);
        
        Carbon::setTestNow();
    }

    public function test_mentor_can_unban_user()
    {
        $ban = S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Test ban',
            'banned_at' => now(),
            'expires_at' => now()->addDays(30),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        S1UserBan::where('user_id', $this->trainee->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $ban->refresh();
        $this->assertFalse($ban->is_active);
    }

    public function test_mentor_can_add_internal_comment()
    {
        $comment = S1TraineeComment::create([
            'user_id' => $this->trainee->id,
            'author_id' => $this->mentor->id,
            'comment' => 'Internal note about training',
            'is_internal' => true,
        ]);

        $this->assertInstanceOf(S1TraineeComment::class, $comment);
        $this->assertTrue($comment->is_internal);
        $this->assertEquals($this->trainee->id, $comment->user_id);
        $this->assertEquals($this->mentor->id, $comment->author_id);
    }

    public function test_mentor_can_add_public_comment()
    {
        $comment = S1TraineeComment::create([
            'user_id' => $this->trainee->id,
            'author_id' => $this->mentor->id,
            'comment' => 'Public feedback',
            'is_internal' => false,
        ]);

        $this->assertFalse($comment->is_internal);
    }

    public function test_progress_reset_for_all_modules()
    {
        $module1 = S1Module::factory()->create(['sequence_order' => 1]);
        $module2 = S1Module::factory()->create(['sequence_order' => 2]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module1->id,
        ]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module2->id,
        ]);

        [$success, $message, $reset] = $this->progressResetService->resetUserProgress(
            $this->trainee,
            $this->mentor->id,
            'Training issues - full reset required'
        );

        $this->assertTrue($success);
        $this->assertInstanceOf(S1ProgressReset::class, $reset);
        
        $this->assertEquals(0, S1ModuleCompletion::where('user_id', $this->trainee->id)
            ->where('was_reset', false)
            ->count());
    }

    public function test_progress_reset_for_specific_modules()
    {
        $module1 = S1Module::factory()->create(['sequence_order' => 1]);
        $module2 = S1Module::factory()->create(['sequence_order' => 2]);
        $module3 = S1Module::factory()->create(['sequence_order' => 3]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module1->id,
        ]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module2->id,
        ]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module3->id,
        ]);

        [$success, $message, $reset] = $this->progressResetService->resetUserProgress(
            $this->trainee,
            $this->mentor->id,
            'Reset Module 2 only',
            [$module2->id]
        );

        $this->assertTrue($success);
        
        $this->assertNull(S1ModuleCompletion::where('user_id', $this->trainee->id)
            ->where('module_id', $module2->id)
            ->where('was_reset', false)
            ->first());
        
        $this->assertNotNull(S1ModuleCompletion::where('user_id', $this->trainee->id)
            ->where('module_id', $module1->id)
            ->where('was_reset', false)
            ->first());
    }

    public function test_progress_reset_deactivates_waiting_lists()
    {
        $module1 = S1Module::factory()->create(['sequence_order' => 1]);
        $module3 = S1Module::factory()->create(['sequence_order' => 3]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module1->id,
        ]);
        
        S1WaitingList::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module3->id,
            'is_active' => true,
        ]);

        [$success, $message, $reset] = $this->progressResetService->resetUserProgress(
            $this->trainee,
            $this->mentor->id,
            'Full reset'
        );

        $this->assertTrue($success);
        
        $waitingList = S1WaitingList::where('user_id', $this->trainee->id)
            ->where('module_id', $module3->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
    }

    public function test_get_user_reset_history()
    {
        $module1 = S1Module::factory()->create(['sequence_order' => 1]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module1->id,
        ]);

        $this->progressResetService->resetUserProgress(
            $this->trainee,
            $this->mentor->id,
            'First reset'
        );
        
        Carbon::setTestNow(now()->addDays(10));
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->trainee->id,
            'module_id' => $module1->id,
        ]);
        
        $this->progressResetService->resetUserProgress(
            $this->trainee,
            $this->mentor->id,
            'Second reset'
        );

        $history = $this->progressResetService->getUserResetHistory($this->trainee);

        $this->assertCount(2, $history);
        $this->assertEquals('Second reset', $history->first()->reason);
        
        Carbon::setTestNow();
    }

    public function test_reset_without_completions_returns_error()
    {
        [$success, $message, $reset] = $this->progressResetService->resetUserProgress(
            $this->trainee,
            $this->mentor->id,
            'No completions to reset'
        );

        $this->assertFalse($success);
        $this->assertStringContainsString('No completed modules', $message);
    }

    public function test_active_bans_scope()
    {
        S1UserBan::create([
            'user_id' => $this->trainee->id,
            'reason' => 'Active ban',
            'banned_at' => now(),
            'expires_at' => now()->addDays(30),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);
        
        $expiredUser = User::factory()->create();
        S1UserBan::create([
            'user_id' => $expiredUser->id,
            'reason' => 'Expired ban',
            'banned_at' => now()->subDays(60),
            'expires_at' => now()->subDays(30),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => true,
        ]);
        
        $inactiveUser = User::factory()->create();
        S1UserBan::create([
            'user_id' => $inactiveUser->id,
            'reason' => 'Inactive ban',
            'banned_at' => now(),
            'expires_at' => now()->addDays(30),
            'banned_by_mentor_id' => $this->mentor->id,
            'is_active' => false,
        ]);

        $activeBans = S1UserBan::active()->get();

        $this->assertCount(1, $activeBans);
        $this->assertEquals($this->trainee->id, $activeBans->first()->user_id);
    }

    public function test_comment_scopes()
    {
        S1TraineeComment::create([
            'user_id' => $this->trainee->id,
            'author_id' => $this->mentor->id,
            'comment' => 'Internal comment',
            'is_internal' => true,
        ]);
        
        S1TraineeComment::create([
            'user_id' => $this->trainee->id,
            'author_id' => $this->mentor->id,
            'comment' => 'Public comment',
            'is_internal' => false,
        ]);

        $internalComments = S1TraineeComment::internal()->get();
        $publicComments = S1TraineeComment::public()->get();

        $this->assertCount(1, $internalComments);
        $this->assertCount(1, $publicComments);
        $this->assertEquals('Internal comment', $internalComments->first()->comment);
        $this->assertEquals('Public comment', $publicComments->first()->comment);
    }
}