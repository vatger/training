<?php

namespace App\Jobs\S1;

use App\Models\S1\S1Module;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1WaitingList;
use App\Services\S1\S1ActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckNextModuleSignupDeadlines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(S1ActivityService $activityService): void
    {
        Log::info('S1: Checking next module signup deadlines');

        $modules = S1Module::active()->ordered()->get();
        $markedInactive = 0;

        foreach ($modules as $module) {
            // Skip the last module (no next module to sign up for)
            $nextModule = S1Module::where('sequence_order', $module->sequence_order + 1)
                ->active()
                ->first();

            if (!$nextModule) {
                continue;
            }

            // Skip if next module is Module 2 (it's automatic, no waiting list)
            if ($nextModule->sequence_order === 2) {
                continue;
            }

            $completions = S1ModuleCompletion::where('module_id', $module->id)
                ->with('user')
                ->get();

            foreach ($completions as $completion) {
                $user = $completion->user;

                // Check if user has signed up for next module
                $hasNextModuleSignup = S1WaitingList::where('user_id', $user->id)
                    ->where('module_id', $nextModule->id)
                    ->exists();

                // Check if user has completed next module
                $hasNextModuleCompletion = S1ModuleCompletion::where('user_id', $user->id)
                    ->where('module_id', $nextModule->id)
                    ->exists();

                // Check if user has completed any module beyond the next one
                $hasFurtherCompletion = S1ModuleCompletion::where('user_id', $user->id)
                    ->whereHas('module', function ($q) use ($nextModule) {
                        $q->where('sequence_order', '>', $nextModule->sequence_order);
                    })
                    ->exists();

                // Skip if user has already signed up or completed next/further modules
                if ($hasNextModuleSignup || $hasNextModuleCompletion || $hasFurtherCompletion) {
                    continue;
                }

                $daysSinceCompletion = now()->diffInDays($completion->completed_at);
                
                if ($daysSinceCompletion > S1WaitingList::NEXT_MODULE_SIGNUP_DEADLINE_DAYS) {
                    // Mark any active waiting lists as inactive
                    S1WaitingList::where('user_id', $user->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    $markedInactive++;

                    Log::info('User marked inactive - missed next module signup deadline', [
                        'user_id' => $user->id,
                        'completed_module' => $module->name,
                        'expected_module' => $nextModule->name,
                        'days_since_completion' => $daysSinceCompletion,
                    ]);
                }
            }
        }

        Log::info('S1: Next module signup deadline check complete', [
            'marked_inactive' => $markedInactive,
        ]);
    }
}