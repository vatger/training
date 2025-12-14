<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'theme' => 'system',
                'english_only' => false,
                'notification_preferences' => [
                    'training_started' => true,
                    'waiting_list_joined' => true,
                    'waiting_list_left' => true,
                    'endorsement_granted' => true,
                    'endorsement_removal' => true,
                    'solo_granted' => true,
                    'solo_extended' => true,
                    'course_completed' => true,
                    'training_log_created' => true,
                    'cpt_scheduled' => true,
                    'cpt_graded' => true,
                ],
            ]
        );

        return Inertia::render('settings/index', [
            'settings' => [
                'theme' => $settings->theme,
                'english_only' => $settings->english_only,
                'notification_preferences' => $settings->notification_preferences ?? [],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'theme' => 'required|in:light,dark,system',
            'english_only' => 'required|boolean',
            'notification_preferences' => 'required|array',
            'notification_preferences.*' => 'boolean',
        ]);

        $settings = UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()->back()
            ->withCookie(cookie()->forget('appearance'))
            ->with('flash', [
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);
    }
}