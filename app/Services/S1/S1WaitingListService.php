<?php

namespace App\Services\S1;

use App\Models\User;
use App\Models\S1\S1Module;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1UserBan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class S1WaitingListService
{
    public function canJoinWaitingList(User $user, S1Module $module): array
    {
        if (!$user->isVatsimUser()) {
            return [false, 'VATSIM account required'];
        }

        if ($this->isUserBanned($user)) {
            return [false, 'You are currently banned from joining waiting lists'];
        }

        if ($this->hasActiveWaitingListForModule($user, $module)) {
            return [false, 'You are already on the waiting list for this module'];
        }

        $requiredModule = $this->getRequiredPreviousModule($module);
        if ($requiredModule && !$this->hasCompletedModule($user, $requiredModule)) {
            return [false, "You must complete {$requiredModule->name} first"];
        }

        if ($this->hasCompletedModule($user, $module)) {
            return [false, 'You have already completed this module'];
        }

        return [true, ''];
    }

    public function joinWaitingList(User $user, S1Module $module): array
    {
        [$canJoin, $reason] = $this->canJoinWaitingList($user, $module);

        if (!$canJoin) {
            return [false, $reason];
        }

        try {
            $waitingList = DB::transaction(function () use ($user, $module) {
                $confirmationDays = config('s1.waiting_list_confirmation_days', 30);
                $expiryDays = config('s1.waiting_list_expiry_days', 90);

                return S1WaitingList::create([
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'joined_at' => now(),
                    'last_confirmed_at' => now(),
                    'confirmation_due_at' => now()->addDays($confirmationDays),
                    'expires_at' => now()->addDays($expiryDays),
                    'is_active' => true,
                ]);
            });

            Log::info('User joined S1 waiting list', [
                'user_id' => $user->id,
                'module_id' => $module->id,
                'position' => $waitingList->position_in_queue,
            ]);

            return [true, 'Successfully joined waiting list'];
        } catch (\Exception $e) {
            Log::error('Failed to join S1 waiting list', [
                'user_id' => $user->id,
                'module_id' => $module->id,
                'error' => $e->getMessage(),
            ]);

            return [false, 'Failed to join waiting list. Please try again.'];
        }
    }

    public function leaveWaitingList(User $user, S1Module $module): array
    {
        try {
            $waitingList = S1WaitingList::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('is_active', true)
                ->first();

            if (!$waitingList) {
                return [false, 'You are not on the waiting list for this module'];
            }

            $waitingList->update(['is_active' => false]);

            Log::info('User left S1 waiting list', [
                'user_id' => $user->id,
                'module_id' => $module->id,
            ]);

            return [true, 'Successfully left waiting list'];
        } catch (\Exception $e) {
            Log::error('Failed to leave S1 waiting list', [
                'user_id' => $user->id,
                'module_id' => $module->id,
                'error' => $e->getMessage(),
            ]);

            return [false, 'Failed to leave waiting list. Please try again.'];
        }
    }

    public function confirmWaitingList(S1WaitingList $waitingList): array
    {
        try {
            $waitingList->confirm();

            Log::info('S1 waiting list confirmed', [
                'waiting_list_id' => $waitingList->id,
                'user_id' => $waitingList->user_id,
            ]);

            return [true, 'Waiting list position confirmed'];
        } catch (\Exception $e) {
            Log::error('Failed to confirm S1 waiting list', [
                'waiting_list_id' => $waitingList->id,
                'error' => $e->getMessage(),
            ]);

            return [false, 'Failed to confirm waiting list. Please try again.'];
        }
    }

    public function deactivateExpiredWaitingLists(): int
    {
        $expired = S1WaitingList::expired()->get();

        foreach ($expired as $waitingList) {
            $waitingList->update(['is_active' => false]);

            Log::info('S1 waiting list expired', [
                'waiting_list_id' => $waitingList->id,
                'user_id' => $waitingList->user_id,
                'module_id' => $waitingList->module_id,
            ]);
        }

        return $expired->count();
    }

    protected function isUserBanned(User $user): bool
    {
        return S1UserBan::where('user_id', $user->id)
            ->active()
            ->exists();
    }

    protected function hasActiveWaitingListForModule(User $user, S1Module $module): bool
    {
        return S1WaitingList::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->exists();
    }

    protected function hasCompletedModule(User $user, S1Module $module): bool
    {
        return S1ModuleCompletion::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->exists();
    }

    protected function getRequiredPreviousModule(S1Module $module): ?S1Module
    {
        if ($module->sequence_order <= 1) {
            return null;
        }

        return S1Module::where('sequence_order', $module->sequence_order - 1)
            ->active()
            ->first();
    }
}