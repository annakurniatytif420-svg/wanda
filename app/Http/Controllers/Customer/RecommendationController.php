<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\CustomerProfile;
use App\Models\MuaProfile;
use App\Services\EnhancedRecommendationService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(EnhancedRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        $profile = Auth::user()->customerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Please complete your skin profile first'], 422);
        }

        try {
            // Use enhanced recommendation service
            $recommendations = $this->recommendationService->getRecommendations($profile, 10);

            // Transform recommendations to match expected format
            $recommendedMuas = collect($recommendations)->map(function ($recommendation) {
                $mua = $recommendation['mua'];
                
                // Add enhanced attributes
                $minPrice = \App\Models\Service::where('mua_id', $mua->user_id)->min('price');
                $mua->starting_price = $minPrice ?? 0;
                $mua->match_score = $recommendation['score'];
                $mua->match_type = $recommendation['match_type'];
                $mua->match_details = $recommendation['match_details'];

                return $mua;
            });

            return response()->json([
                'recommended' => $recommendedMuas,
                'total_count' => $recommendedMuas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating recommendations', [
                'customer_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            // Fallback to basic recommendations
            return $this->getBasicRecommendations($profile);
        }
    }

    /**
     * Fallback basic recommendations when enhanced fails
     *
     * @param CustomerProfile $profile
     * @return \Illuminate\Http\JsonResponse
     */
    private function getBasicRecommendations(CustomerProfile $profile)
    {
        $muas = MuaProfile::with('user')
            ->whereHas('user', function ($query) {
                // Users table doesn't have 'status' column, so we'll just check if user exists
                $query->whereNotNull('id');
            })
            ->limit(10)
            ->get();

        $muas->map(function ($muaProfile) {
            $minPrice = \App\Models\Service::where('mua_id', $muaProfile->user_id)->min('price');
            $muaProfile->starting_price = $minPrice ?? 0;
            $muaProfile->match_score = 50;
            $muaProfile->match_type = 'basic';
            return $muaProfile;
        });

        return response()->json([
            'recommended' => $muas,
            'total_count' => $muas->count(),
            'fallback' => true
        ]);
    }
}
