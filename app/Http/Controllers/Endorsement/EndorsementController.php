<?php

namespace App\Http\Controllers\Endorsement;

use App\Domain\Endorsement\Actions\GrantTier2Endorsement;
use App\Domain\Endorsement\Actions\MarkEndorsementForRemoval;
use App\Http\Controllers\Controller;
use App\Models\EndorsementActivity;
use App\Models\Tier2Endorsement;
use App\Services\EndorsementViewService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EndorsementController extends Controller
{
    public function __construct(
        private EndorsementViewService $viewService,
        private MarkEndorsementForRemoval $markForRemoval,
        private GrantTier2Endorsement $grantTier2,
    ) {}

    public function traineeView(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isVatsimUser()) {
            return Inertia::render('endorsements/trainee', [
                'tier1Endorsements' => [],
                'tier2Endorsements' => [],
                'soloEndorsements' => [],
                'isVatsimUser' => false,
            ]);
        }

        try {
            return Inertia::render('endorsements/trainee', $this->viewService->getTraineeData($user));
        } catch (\Exception) {
            return Inertia::render('endorsements/trainee', [
                'tier1Endorsements' => [],
                'tier2Endorsements' => [],
                'soloEndorsements' => [],
                'isVatsimUser' => true,
                'error' => 'Failed to load endorsement data',
            ]);
        }
    }

    public function mentorView(Request $request): Response
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            abort(403, 'Access denied. Mentor privileges required.');
        }

        return Inertia::render('endorsements/manage', $this->viewService->getMentorData($user));
    }

    public function removeTier1(Request $request, int $endorsementId)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return back()->with('flash', ['error' => 'Access denied. Mentor privileges required.']);
        }

        $activity = EndorsementActivity::where('endorsement_id', $endorsementId)->first();

        if (! $activity) {
            return back()->with('flash', ['error' => 'Endorsement not found in the system.']);
        }

        if (! $user->is_superuser && ! $user->is_admin) {
            $lmFirs = $user->leadingMentorFirs()->pluck('fir');
            $hasPermission = $user->canRemoveEndorsementForPosition($activity->position)
                || ($lmFirs->isNotEmpty() && $this->viewService->positionMatchesLmFir($activity->position, $lmFirs));

            if (! $hasPermission) {
                return back()->with('flash', [
                    'error' => 'You do not have permission to manage this endorsement. Only Chief of Training or Leading Mentor for this position can remove endorsements.',
                ]);
            }
        }

        try {
            $this->markForRemoval->execute($activity, $user);
        } catch (ValidationException $e) {
            return back()->with('flash', ['error' => $e->errors()['endorsement'][0]]);
        }

        return back()->with('flash', ['success' => "Successfully marked {$activity->position} for removal"]);
    }

    public function requestTier2(Request $request, int $tier2Id)
    {
        $user = $request->user();

        if (! $user->isVatsimUser()) {
            return back()->with('error', 'VATSIM account required');
        }

        $tier2Endorsement = Tier2Endorsement::findOrFail($tier2Id);

        try {
            $this->grantTier2->execute($tier2Endorsement, $user);
        } catch (ValidationException $e) {
            return back()->with('error', $e->errors()['endorsement'][0]);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Failed to create endorsement');
        }

        return redirect()->route('endorsements.trainee')->with('success', 'Tier 2 endorsement granted successfully');
    }
}
