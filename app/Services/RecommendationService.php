<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\MuaProfile;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    /**
     * Get recommended MUAs for a customer based on skin type and makeup preferences
     *
     * @param CustomerProfile $customerProfile
     * @param int $limit
     * @return array
     */
    public function getRecommendations(CustomerProfile $customerProfile, int $limit = 10): array
    {
        $customerSkinTypes = $customerProfile->skin_type ?? [];
        $customerMakeupPreferences = $customerProfile->makeup_preferences ?? [];

        Log::info('Getting recommendations for customer', [
            'customer_id' => $customerProfile->user_id,
            'skin_types' => $customerSkinTypes,
            'makeup_preferences' => $customerMakeupPreferences
        ]);

        // Get all active MUAs
        $muas = MuaProfile::with(['user', 'user.services', 'user.portfolios'])->get();


        // Calculate match scores
        $scoredMuas = [];
        foreach ($muas as $mua) {
            $score = $this->calculateMatchScore(
                $customerSkinTypes,
                $customerMakeupPreferences,
                $mua
            );

            // Tambahkan log per MUA
            Log::info('MUA match score calculated', [
                'mua_id' => $mua->user_id ?? $mua->id ?? null,
                'score' => $score,
                'name' => $mua->user->name ?? '(no name)',
            ]);

            if ($score > 0) {
                $scoredMuas[] = [
                    'mua' => $mua,
                    'score' => $score,
                    'match_details' => $this->getMatchDetails(
                        $customerSkinTypes,
                        $customerMakeupPreferences,
                        $mua
                    )
                ];
            }
        }

        // Sort by score descending
        usort($scoredMuas, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limit results
        $scoredMuas = array_slice($scoredMuas, 0, $limit);

        Log::info('Recommendations calculated', [
            'total_muas' => count($scoredMuas),
            'top_scores' => array_slice(array_column($scoredMuas, 'score'), 0, 5)
        ]);

        return $scoredMuas;
    }

    /**
     * Calculate match score between customer and MUA
     *
     * @param array $customerSkinTypes
     * @param array $customerMakeupPreferences
     * @param MuaProfile $mua
     * @return float
     */
    private function calculateMatchScore(array $customerSkinTypes, array $customerMakeupPreferences, MuaProfile $mua): float
    {
        $score = 0;

        // Increased base score to ensure minimum visibility
        $baseScore = 0.5;

        // Handle empty arrays with fallback scoring
        $hasCustomerData = !empty($customerSkinTypes) || !empty($customerMakeupPreferences);
        $hasMuaData = !empty($mua->skin_type) || !empty($mua->makeup_styles) || !empty($mua->makeup_specializations);

        // If either profile has no data, use enhanced base score
        if (!$hasCustomerData || !$hasMuaData) {
            return $baseScore;
        }

        // Skin type matching (40% weight) - with better handling
        $skinTypeScore = $this->calculateSkinTypeMatch($customerSkinTypes, $mua->skin_type ?? []);
        $score += $skinTypeScore * 0.4;

        // Makeup style matching (50% weight) - with better handling
        $makeupStyleScore = $this->calculateMakeupStyleMatch($customerMakeupPreferences, $mua->makeup_styles ?? []);
        $score += $makeupStyleScore * 0.5;

        // Specialization bonus (10% weight) - with better handling
        $specializationScore = $this->calculateSpecializationMatch($customerMakeupPreferences, $mua->makeup_specializations ?? []);
        $score += $specializationScore * 0.1;

        // Enhanced fallback scoring for partial matches
        if ($score < $baseScore && ($skinTypeScore > 0 || $makeupStyleScore > 0)) {
            $score = max($score, $baseScore);
        }

        // Ensure minimum score for active MUAs
        $finalScore = max($score, $baseScore);
        
        return round($finalScore, 2);
    }

    /**
     * Calculate skin type match score
     *
     * @param array $customerSkinTypes
     * @param array $muaSkinTypes
     * @return float
     */
    private function calculateSkinTypeMatch(array $customerSkinTypes, array $muaSkinTypes): float
    {
        if (empty($customerSkinTypes) || empty($muaSkinTypes)) {
            return 0;
        }

        $matches = array_intersect(
            array_map('strtolower', $customerSkinTypes),
            array_map('strtolower', $muaSkinTypes)
        );

        return count($matches) / max(count($customerSkinTypes), count($muaSkinTypes));
    }

    /**
     * Calculate makeup style match score
     *
     * @param array $customerPreferences
     * @param array $muaStyles
     * @return float
     */
    private function calculateMakeupStyleMatch(array $customerPreferences, array $muaStyles): float
    {
        if (empty($customerPreferences) || empty($muaStyles)) {
            return 0;
        }

        $matches = array_intersect(
            array_map('strtolower', $customerPreferences),
            array_map('strtolower', $muaStyles)
        );

        return count($matches) / max(count($customerPreferences), count($muaStyles));
    }

    /**
     * Calculate specialization match score
     *
     * @param array $customerPreferences
     * @param array $muaSpecializations
     * @return float
     */
    private function calculateSpecializationMatch(array $customerPreferences, array $muaSpecializations): float
    {
        if (empty($customerPreferences) || empty($muaSpecializations)) {
            return 0;
        }

        $matches = array_intersect(
            array_map('strtolower', $customerPreferences),
            array_map('strtolower', $muaSpecializations)
        );

        return count($matches) / max(count($customerPreferences), count($muaSpecializations));
    }

    /**
     * Get detailed match information
     *
     * @param array $customerSkinTypes
     * @param array $customerMakeupPreferences
     * @param MuaProfile $mua
     * @return array
     */
    private function getMatchDetails(array $customerSkinTypes, array $customerMakeupPreferences, MuaProfile $mua): array
    {
        $muaSkinTypes = $mua->skin_type ?? [];
        $muaStyles = $mua->makeup_styles ?? [];
        $muaSpecializations = $mua->makeup_specializations ?? [];

        return [
            'skin_type_matches' => array_values(array_intersect(
                array_map('strtolower', $customerSkinTypes),
                array_map('strtolower', $muaSkinTypes)
            )),
            'makeup_style_matches' => array_values(array_intersect(
                array_map('strtolower', $customerMakeupPreferences),
                array_map('strtolower', $muaStyles)
            )),
            'specialization_matches' => array_values(array_intersect(
                array_map('strtolower', $customerMakeupPreferences),
                array_map('strtolower', $muaSpecializations)
            )),
            'customer_skin_types' => $customerSkinTypes,
            'mua_skin_types' => $muaSkinTypes,
            'customer_makeup_preferences' => $customerMakeupPreferences,
            'mua_makeup_styles' => $muaStyles,
            'mua_specializations' => $muaSpecializations
        ];
    }

    /**
     * Get compatible skin types for a given skin type
     *
     * @param string $skinType
     * @return array
     */
    public function getCompatibleSkinTypes(string $skinType): array
    {
        $compatibilityMap = [
            'normal' => ['normal', 'combination', 'all'],
            'dry' => ['dry', 'normal', 'combination', 'all'],
            'oily' => ['oily', 'combination', 'all'],
            'combination' => ['combination', 'normal', 'oily', 'dry', 'all'],
            'sensitive' => ['sensitive', 'normal', 'all'],
            'all' => ['all', 'normal', 'dry', 'oily', 'combination', 'sensitive']
        ];

        return $compatibilityMap[strtolower($skinType)] ?? [$skinType, 'all'];
    }
}
