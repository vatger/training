<?php

namespace App\Services\S1;

use App\Models\S1\S1Session;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1WaitingList;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class S1NotificationService
{
    public function sendSessionSelectionNotifications(S1Session $session): array
    {
        try {
            $selectedSignups = S1SessionSignup::where('session_id', $session->id)
                ->where('was_selected', true)
                ->where('notification_sent', false)
                ->with('user')
                ->get();

            $sent = 0;
            foreach ($selectedSignups as $signup) {
                // TODO: Send actual notification
                $signup->update(['notification_sent' => true]);
                $sent++;
            }

            $rejectedSignups = S1SessionSignup::where('session_id', $session->id)
                ->where('was_selected', false)
                ->where('notification_sent', false)
                ->with('user')
                ->get();

            foreach ($rejectedSignups as $signup) {
                // TODO: Send rejection notification
                $signup->update(['notification_sent' => true]);
            }

            return [true, "Sent {$sent} selection notifications"];
        } catch (\Exception $e) {
            Log::error('Failed to send S1 session selection notifications', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            return [false, 'Failed to send notifications'];
        }
    }

    public function sendNewSessionNotification(S1Session $session): array
    {
        try {
            $waitingLists = S1WaitingList::where('module_id', $session->module_id)
                ->where('is_active', true)
                ->with('user')
                ->get();

            $sent = 0;
            foreach ($waitingLists as $waitingList) {
                // TODO: Send new session notification
                $sent++;
            }

            return [true, "Sent {$sent} new session notifications"];
        } catch (\Exception $e) {
            Log::error('Failed to send S1 new session notifications', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            return [false, 'Failed to send notifications'];
        }
    }

    public function sendConfirmationReminders(): array
    {
        try {
            $waitingLists = S1WaitingList::active()
                ->needingConfirmation()
                ->with('user', 'module')
                ->get();

            $sent = 0;
            foreach ($waitingLists as $waitingList) {
                // TODO: Send confirmation reminder
                $sent++;
            }

            return [true, "Sent {$sent} confirmation reminders"];
        } catch (\Exception $e) {
            Log::error('Failed to send S1 confirmation reminders', [
                'error' => $e->getMessage(),
            ]);
            return [false, 'Failed to send reminders'];
        }
    }

    public function sendExpiryWarnings(): array
    {
        try {
            $threeDaysFromNow = now()->addDays(3);
            
            $expiringLists = S1WaitingList::active()
                ->where('expires_at', '<=', $threeDaysFromNow)
                ->where('expires_at', '>', now())
                ->with('user', 'module')
                ->get();

            $sent = 0;
            foreach ($expiringLists as $waitingList) {
                // TODO: Send expiry warning
                $sent++;
            }

            return [true, "Sent {$sent} expiry warnings"];
        } catch (\Exception $e) {
            Log::error('Failed to send S1 expiry warnings', [
                'error' => $e->getMessage(),
            ]);
            return [false, 'Failed to send warnings'];
        }
    }
}