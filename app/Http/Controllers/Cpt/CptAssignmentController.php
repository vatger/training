<?php

namespace App\Http\Controllers\Cpt;

use App\Domain\Cpt\Actions\JoinCptAsExaminer;
use App\Domain\Cpt\Actions\JoinCptAsLocal;
use App\Domain\Cpt\Actions\LeaveCptAsExaminer;
use App\Domain\Cpt\Actions\LeaveCptAsLocal;
use App\Http\Controllers\Controller;
use App\Models\Cpt;
use Illuminate\Http\Request;

class CptAssignmentController extends Controller
{
    public function __construct(
        private readonly JoinCptAsExaminer $joinExaminerAction,
        private readonly LeaveCptAsExaminer $leaveExaminerAction,
        private readonly JoinCptAsLocal $joinLocalAction,
        private readonly LeaveCptAsLocal $leaveLocalAction,
    ) {}

    public function joinExaminer(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $user->load('examiner');
        $cpt->load('course.mentors');

        if ($cpt->examiner_id === $user->id) {
            return back()->withErrors(['error' => 'You are already assigned as examiner for this CPT.']);
        }

        if ($cpt->local_id === $user->id) {
            return back()->withErrors(['error' => 'You cannot be both examiner and local contact.']);
        }

        if (!$user->examiner) {
            return back()->withErrors(['error' => 'You must have an examiner profile to join as examiner.']);
        }

        if (!$user->examiner->hasPosition($cpt->course->position)) {
            return back()->withErrors(['error' => 'You are not authorized to examine this position (' . $cpt->course->position . ').']);
        }

        if ($cpt->course->mentors->contains($user->id) && $cpt->date->diffInHours(now()) > 36) {
            return back()->withErrors(['error' => 'Course mentors cannot be examiners more than 36 hours in advance.']);
        }

        $this->joinExaminerAction->execute($cpt, $user);

        return back()->with('success', 'Successfully joined as examiner.');
    }

    public function leaveExaminer(Request $request, Cpt $cpt)
    {
        $user = $request->user();

        if ($cpt->examiner_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not the examiner for this CPT.']);
        }

        $this->leaveExaminerAction->execute($cpt, $user);

        return back()->with('success', 'Successfully left as examiner.');
    }

    public function joinLocal(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $cpt->load('course.mentors');

        if ($cpt->local_id === $user->id) {
            return back()->withErrors(['error' => 'You are already assigned as local contact for this CPT.']);
        }

        if ($cpt->examiner_id === $user->id) {
            return back()->withErrors(['error' => 'You cannot be both examiner and local contact.']);
        }

        if (!$cpt->course->mentors->contains($user->id)) {
            return back()->withErrors(['error' => 'You must be a mentor of this course to join as local contact.']);
        }

        $this->joinLocalAction->execute($cpt, $user);

        return back()->with('success', 'Successfully joined as local contact.');
    }

    public function leaveLocal(Request $request, Cpt $cpt)
    {
        $user = $request->user();

        if ($cpt->local_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not the local contact for this CPT.']);
        }

        $this->leaveLocalAction->execute($cpt, $user);

        return back()->with('success', 'Successfully left as local contact.');
    }
}