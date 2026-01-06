<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Module;
use App\Models\S1\S1WaitingList;
use App\Services\S1\S1WaitingListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class S1WaitingListController extends Controller
{
    protected $waitingListService;

    public function __construct(S1WaitingListService $waitingListService)
    {
        $this->waitingListService = $waitingListService;
    }

    public function join(Request $request, S1Module $module): RedirectResponse
    {
        $request->validate([
            'confirm_requirements' => 'required|boolean|accepted',
        ]);

        $user = $request->user();

        [$canJoin, $reason] = $this->waitingListService->canJoinWaitingList($user, $module);

        if (!$canJoin) {
            return redirect()->back()->with('error', $reason);
        }

        [$success, $message, $waitingList] = $this->waitingListService->joinWaitingList($user, $module);

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Successfully joined waiting list for ' . $module->name);
    }

    public function leave(Request $request, S1Module $module): RedirectResponse
    {
        $user = $request->user();

        [$success, $message] = $this->waitingListService->leaveWaitingList($user, $module);

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Successfully left waiting list for ' . $module->name);
    }

    public function confirm(Request $request, S1WaitingList $waitingList): RedirectResponse
    {
        if ($waitingList->user_id !== $request->user()->id) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if (!$waitingList->is_active) {
            return redirect()->back()->with('error', 'Waiting list entry is not active');
        }

        [$success, $message, $updatedWaitingList] = $this->waitingListService->confirmWaitingList($waitingList);

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Waiting list position confirmed successfully');
    }
}