<?php

namespace Tests\Feature\S1;

use App\Models\User;
use App\Models\S1\S1Module;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1ModuleCompletion;
use App\Services\S1\S1WaitingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WaitingListTest extends TestCase
{
    use RefreshDatabase;

    protected S1WaitingListService $waitingListService;
    protected User $user;
    protected S1Module $module1;
    protected S1Module $module2;
    protected S1Module $module3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->waitingListService = app(S1WaitingListService::class);
        
        $this->user = User::factory()->create([
            'vatsim_id' => 1234567,
            'subdivision' => 'GER',
            'rating' => 1,
        ]);
        
        $this->module1 = S1Module::factory()->create(['sequence_order' => 1, 'name' => 'Module 1']);
        $this->module2 = S1Module::factory()->create(['sequence_order' => 2, 'name' => 'Module 2']);
        $this->module3 = S1Module::factory()->create(['sequence_order' => 3, 'name' => 'Module 3']);
    }

    public function test_user_can_join_module_1_waiting_list()
    {
        [$success, $message, $waitingList] = $this->waitingListService->joinWaitingList($this->user, $this->module1);

        $this->assertTrue($success);
        $this->assertInstanceOf(S1WaitingList::class, $waitingList);
        $this->assertEquals($this->user->id, $waitingList->user_id);
        $this->assertEquals($this->module1->id, $waitingList->module_id);
        $this->assertTrue($waitingList->is_active);
        $this->assertNotNull($waitingList->confirmation_due_at);
        $this->assertNotNull($waitingList->expires_at);
    }

    public function test_user_cannot_join_waiting_list_twice()
    {
        $this->waitingListService->joinWaitingList($this->user, $this->module1);
        
        [$success, $message] = $this->waitingListService->joinWaitingList($this->user, $this->module1);

        $this->assertFalse($success);
        $this->assertStringContainsString('already on the waiting list', $message);
    }

    public function test_user_cannot_join_module_2_waiting_list()
    {
        [$success, $message] = $this->waitingListService->joinWaitingList($this->user, $this->module2);

        $this->assertFalse($success);
        $this->assertStringContainsString('Module 2 does not have a waiting list', $message);
    }

    public function test_user_must_complete_previous_module_before_joining_next()
    {
        [$success, $message] = $this->waitingListService->joinWaitingList($this->user, $this->module3);

        $this->assertFalse($success);
        $this->assertStringContainsString('must complete', $message);
    }

    public function test_user_can_join_module_3_after_completing_module_2()
    {
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
        ]);
        
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module2->id,
        ]);

        [$success, $message, $waitingList] = $this->waitingListService->joinWaitingList($this->user, $this->module3);

        $this->assertTrue($success);
        $this->assertInstanceOf(S1WaitingList::class, $waitingList);
    }

    public function test_user_can_leave_waiting_list()
    {
        $this->waitingListService->joinWaitingList($this->user, $this->module1);
        
        [$success, $message] = $this->waitingListService->leaveWaitingList($this->user, $this->module1);

        $this->assertTrue($success);
        
        $waitingList = S1WaitingList::where('user_id', $this->user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
    }

    public function test_waiting_list_position_is_based_on_join_date()
    {
        $user1 = User::factory()->create(['vatsim_id' => 1000001, 'subdivision' => 'GER']);
        $user2 = User::factory()->create(['vatsim_id' => 1000002, 'subdivision' => 'GER']);
        $user3 = User::factory()->create(['vatsim_id' => 1000003, 'subdivision' => 'GER']);

        Carbon::setTestNow('2024-01-01 10:00:00');
        $this->waitingListService->joinWaitingList($user1, $this->module1);
        
        Carbon::setTestNow('2024-01-01 11:00:00');
        $this->waitingListService->joinWaitingList($user2, $this->module1);
        
        Carbon::setTestNow('2024-01-01 12:00:00');
        $this->waitingListService->joinWaitingList($user3, $this->module1);

        $wl1 = S1WaitingList::where('user_id', $user1->id)->first();
        $wl2 = S1WaitingList::where('user_id', $user2->id)->first();
        $wl3 = S1WaitingList::where('user_id', $user3->id)->first();

        $this->assertEquals(1, $wl1->position_in_queue);
        $this->assertEquals(2, $wl2->position_in_queue);
        $this->assertEquals(3, $wl3->position_in_queue);
        
        Carbon::setTestNow();
    }

    public function test_user_can_confirm_waiting_list_position()
    {
        $this->waitingListService->joinWaitingList($this->user, $this->module1);
        
        $waitingList = S1WaitingList::where('user_id', $this->user->id)->first();
        $oldConfirmationDate = $waitingList->last_confirmed_at;
        
        Carbon::setTestNow(now()->addDays(31));
        
        [$success, $message, $updatedWaitingList] = $this->waitingListService->confirmWaitingList($waitingList);

        $this->assertTrue($success);
        $this->assertTrue($updatedWaitingList->last_confirmed_at->isAfter($oldConfirmationDate));
        
        Carbon::setTestNow();
    }

    public function test_waiting_list_expires_after_expiry_date()
    {
        Carbon::setTestNow('2024-01-01');
        
        $this->waitingListService->joinWaitingList($this->user, $this->module1);
        
        $waitingList = S1WaitingList::where('user_id', $this->user->id)->first();
        $waitingList->update(['expires_at' => now()->addDays(1)]);
        
        Carbon::setTestNow('2024-01-03');
        
        [$success, $message, $count] = $this->waitingListService->deactivateExpiredWaitingLists();

        $this->assertTrue($success);
        $this->assertEquals(1, $count);
        
        $waitingList->refresh();
        $this->assertFalse($waitingList->is_active);
        
        Carbon::setTestNow();
    }

    public function test_waiting_list_needs_confirmation_check()
    {
        Carbon::setTestNow('2024-01-01');
        
        $this->waitingListService->joinWaitingList($this->user, $this->module1);
        
        $waitingList = S1WaitingList::where('user_id', $this->user->id)->first();
        $waitingList->update(['confirmation_due_at' => now()->addDays(30)]);
        
        Carbon::setTestNow('2024-02-01');
        
        $this->assertTrue($waitingList->needsConfirmation());
        
        Carbon::setTestNow();
    }

    public function test_user_cannot_join_if_already_completed_module()
    {
        S1ModuleCompletion::factory()->create([
            'user_id' => $this->user->id,
            'module_id' => $this->module1->id,
        ]);

        [$success, $message] = $this->waitingListService->joinWaitingList($this->user, $this->module1);

        $this->assertFalse($success);
        $this->assertStringContainsString('already completed', $message);
    }

    public function test_inactive_waiting_list_can_be_reactivated()
    {
        $this->waitingListService->joinWaitingList($this->user, $this->module1);
        $this->waitingListService->leaveWaitingList($this->user, $this->module1);
        
        $waitingList = S1WaitingList::where('user_id', $this->user->id)
            ->where('module_id', $this->module1->id)
            ->first();
            
        $this->assertFalse($waitingList->is_active);
        
        $waitingList->reactivate();
        
        $this->assertTrue($waitingList->is_active);
        $this->assertNotNull($waitingList->confirmation_due_at);
        $this->assertNotNull($waitingList->expires_at);
    }
}