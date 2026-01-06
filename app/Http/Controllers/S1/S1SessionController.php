<?php

namespace App\Http\Controllers\S1;

use App\Http\Controllers\Controller;
use App\Models\S1\S1Session;
use App\Services\S1\S1SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class S1SessionController extends Controller
{
    protected $sessionService;

    public function __construct(S1SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function signup(Request $request, S1Session $session): RedirectResponse
    {
        $user = $request->user();

        [$success, $message] = $this->sessionService->signupForSession($session, $user);

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Successfully signed up for session on ' . $session->scheduled_at->format('M d, Y \a\t H:i'));
    }

    public function cancelSignup(Request $request, S1Session $session): RedirectResponse
    {
        $user = $request->user();

        [$success, $message] = $this->sessionService->cancelSignup($session, $user);

        if (!$success) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Signup cancelled successfully');
    }
}