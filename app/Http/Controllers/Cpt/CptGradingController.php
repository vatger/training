<?php

namespace App\Http\Controllers\Cpt;

use App\Domain\Cpt\Actions\GradeCpt;
use App\Http\Controllers\Controller;
use App\Models\Cpt;
use Illuminate\Http\Request;

class CptGradingController extends Controller
{
    public function __construct(
        private readonly GradeCpt $gradeCpt,
    ) {}

    public function grade(Request $request, Cpt $cpt, int $result)
    {
        if (! $request->user()->isSuperuser()) {
            return back()->withErrors(['error' => 'Only ATD can grade CPTs.']);
        }

        if ($result !== 0 && $result !== 1) {
            return back()->withErrors(['error' => 'Invalid grading option.']);
        }

        $this->gradeCpt->execute($cpt, $result === 1, $request->user());

        return back()->with('success', 'CPT graded successfully.');
    }
}
