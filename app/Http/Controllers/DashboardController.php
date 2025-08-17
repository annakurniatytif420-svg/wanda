<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuaProfile;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Services\RecommendationService;

class DashboardController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get dashboard data with recommendations
     */
    public function index(Request $request)
    {
        $style     = $request->query('style');
        $spec      = $request->query('specialization');
        $minPrice  = $request->query('min_price');
        $maxPrice  = $request->query('max_price');
        $customerId = $request->query('customer_id');
        $limit = $request->query('limit', 10);

        // If customer_id is provided, use recommendation system
        if ($customerId) {
            return $this->getCustomerRecommendations($customerId, $request);
        }

        // Legacy filtering for backward compatibility
        return $this->getFilteredMuas($request);
    }

    /**
     * Get personalized recommendations for a customer
     */
    private function getCustomerRecommendations(string $customerId, Request $request): \Illuminate\Http\JsonResponse
    {
        $limit = $request->query('limit', 10);

        try {
            $customerProfile = CustomerProfile::where('user_id', $customerId)->first();
            
            if (!$customerProfile) {
                return response()->json([
                    'message' => 'Customer profile not found',
                    'data' => []
                ], 404);
            }

            $recommendations = $this->recommendationService->getRecommendations($customerProfile, $limit);

            return response()->json([
                'message' => 'Recommendations retrieved successfully',
                'data' => $recommendations,
                'total' => count($recommendations)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting customer recommendations', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve recommendations',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get filtered MUAs (legacy method for backward compatibility)
     */
    private function getFilteredMuas(Request $request): \Illuminate\Http\JsonResponse
    {
        $style     = $request->query('style');
        $spec      = $request->query('specialization');
        $minPrice  = $request->query('min_price');
        $maxPrice  = $request->query('max_price');
        $customerSkinTypes = $request->query('skin_types');
        $customerStyles = $request->query('styles');

        $query = MuaProfile::query()
            ->with(['user', 'user.services', 'user.portfolios']);

        if ($style) {
            $query->whereJsonContains('makeup_styles', $style);
        }

        if ($spec) {
            $query->whereJsonContains('makeup_specializations', $spec);
        }

        if ($minPrice || $maxPrice) {
            $query->whereHas('user.services', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice) $q->where('price', '>=', $minPrice);
                if ($maxPrice) $q->where('price', '<=', $maxPrice);
            });
        }

        // Basic filtering based on customer preferences
        if ($customerSkinTypes) {
            $skinTypes = is_array($customerSkinTypes) ? $customerSkinTypes : explode(',', $customerSkinTypes);
            $query->where(function($q) use ($skinTypes) {
                foreach ($skinTypes as $skinType) {
                    $q->orWhereJsonContains('skin_type', trim($skinType));
                }
            });
        }

        if ($customerStyles) {
            $styles = is_array($customerStyles) ? $customerStyles : explode(',', $customerStyles);
            $query->where(function($q) use ($styles) {
                foreach ($styles as $style) {
                    $q->orWhereJsonContains('makeup_styles', trim($style));
                }
            });
        }

        $results = $query->get();

        return response()->json([
            'message' => 'MUAs retrieved successfully',
            'data' => $results,
            'total' => $results->count()
        ]);
    }

    public function getAllMuaWithProfile(Request $request)
    {
        $muaUsers = User::where('role', 'mua')
            ->whereHas('muaProfile')
            ->with([
                'muaProfile',
                'services',
                'portfolios'
            ])
            ->get();

        return response()->json([
            'message' => 'MUA users retrieved successfully',
            'data' => $muaUsers
        ]);
    }
}
