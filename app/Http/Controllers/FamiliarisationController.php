<?php

namespace App\Http\Controllers;

use App\Services\FamiliarisationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamiliarisationController extends Controller
{
    protected FamiliarisationService $familiarisationService;

    public function __construct(FamiliarisationService $familiarisationService)
    {
        $this->familiarisationService = $familiarisationService;
    }

    /**
     * Show all familiarisations
     */
    public function index(Request $request): Response
    {
        try {
            $familiarisations = \App\Models\Familiarisation::query()
                ->with(['user:id,vatsim_id', 'sector:id,name'])
                ->get()
                ->groupBy(fn ($fam) => $fam->user->vatsim_id)
                ->map(function ($userFams, $cid) {
                    return [
                        'cid' => (int) $cid,
                        'stations' => $userFams
                            ->pluck('sector.name')
                            ->sort()
                            ->values()
                            ->all(),
                    ];
                })
                ->sortBy('cid')
                ->values()
                ->all();

            return Inertia::render('training/familiarisations', [
                'familiarisations' => $familiarisations,
                'statistics' => [
                    'total_users' => count($familiarisations),
                    'total_familiarisations' => \App\Models\Familiarisation::count(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error loading familiarisations', [
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('training/familiarisations', [
                'familiarisations' => [],
                'error' => 'Failed to load familiarisations.',
            ]);
        }
    }
}
