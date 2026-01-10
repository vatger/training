<?php

namespace App\Jobs\S1;

use App\Models\S1\S1Module;
use App\Models\S1\S1ModuleCompletion;
use App\Services\S1\S1ActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckModule2Activity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(S1ActivityService $activityService): void
    {
        Log::info('S1: Checking Module 2 activity');

        $module1 = S1Module::where('sequence_order', 1)->first();
        $module2 = S1Module::where('sequence_order', 2)->first();
        $module3 = S1Module::where('sequence_order', 3)->first();
        $module4 = S1Module::where('sequence_order', 4)->first();

        if (!$module1 || !$module2 || !$module3 || !$module4) {
            Log::error('S1: Required modules not found for activity check');
            return;
        }

        $completedModule1 = S1ModuleCompletion::where('module_id', $module1->id)
            ->pluck('user_id');

        $completedModule2 = S1ModuleCompletion::where('module_id', $module2->id)
            ->pluck('user_id');

        $completedModule3 = S1ModuleCompletion::where('module_id', $module3->id)
            ->pluck('user_id');

        $completedModule4 = S1ModuleCompletion::where('module_id', $module4->id)
            ->pluck('user_id');

        $activeModule2Users = $completedModule1
            ->diff($completedModule2)
            ->diff($completedModule3)
            ->diff($completedModule4);

        $inactiveCount = 0;
        foreach ($activeModule2Users as $userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                continue;
            }

            [$success, $message, $status] = $activityService->checkModule2Activity($user);
            
            if ($success && isset($status['is_inactive']) && $status['is_inactive']) {
                $activityService->markModule2Inactive(
                    $user,
                    "No quiz activity for {$status['days_since_last_activity']} days"
                );
                $inactiveCount++;
            }
        }

        Log::info('S1: Module 2 activity check complete', [
            'checked_users' => $activeModule2Users->count(),
            'marked_inactive' => $inactiveCount,
        ]);
    }
}