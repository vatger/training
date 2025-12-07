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
            // Get all familiarisations grouped by user
            $familiarisations = \App\Models\Familiarisation::with(['user', 'sector'])
                ->get()
                ->groupBy('user.vatsim_id');

            $formattedData = [];
            foreach ($familiarisations as $vatsimId => $userFams) {
                $user = $userFams->first()->user;
                $sectors = $userFams->map(function ($fam) {
                    return $fam->sector->name;
                })->sort()->implode(', ');

                $formattedData[] = [
                    'vatsim_id' => $vatsimId,
                    'name' => $user->name,
                    'sectors' => $sectors,
                ];
            }

            // Sort by name
            usort($formattedData, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return Inertia::render('training/familiarisations', [
                'familiarisations' => $formattedData,
                'statistics' => [
                    'total_users' => count($formattedData),
                    'total_familiarisations' => \App\Models\Familiarisation::count(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading familiarisations', [
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('training/familiarisations', [
                'familiarisations' => [],
                'error' => 'Failed to load familiarisations.',
            ]);
        }
    }

    /**
     * Show familiarisations for a specific user
     */
    public function userFamiliarisations(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isVatsimUser()) {
            return Inertia::render('training/my-familiarisations', [
                'familiarisations' => [],
                'isVatsimUser' => false,
            ]);
        }

        try {
            $familiarisations = $this->familiarisationService->getFamiliarisations($user->vatsim_id);

            return Inertia::render('training/my-familiarisations', [
                'familiarisations' => $familiarisations,
                'isVatsimUser' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading user familiarisations', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('training/my-familiarisations', [
                'familiarisations' => [],
                'isVatsimUser' => true,
                'error' => 'Failed to load your familiarisations.',
            ]);
        }
    }
}
