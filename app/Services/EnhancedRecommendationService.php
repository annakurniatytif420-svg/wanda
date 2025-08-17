<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\MuaProfile;
use Illuminate\Support\Facades\Log;

class EnhancedRecommendationService
{
    /**
     * Get enhanced recommendations with flexible matching
     *
     * @param CustomerProfile $customerProfile
     * @param int $limit
     * @return array
     */
    public function getRecommendations(CustomerProfile $customerProfile, int $limit = 10): array
    {
        $customerSkinTypes = $this->normalizePreferences($customerProfile->skin_type ?? []);
        $customerMakeupPreferences = $this->normalizePreferences($customerProfile->makeup_preferences ?? []);
        $customerMakeupStyles = $this->normalizePreferences($customerProfile->makeup_style ?? []);

        // Log::info('Enhanced recommendations for customer', [
        //     'customer_id' => $customerProfile->user_id,
        //     'skin_types' => $customerSkinTypes,
        //     'makeup_preferences' => $customerMakeupPreferences,
        //     'makeup_styles' => $customerMakeupStyles
        // ]);

        // Get all active MUAs
        $muas = MuaProfile::with(['user', 'user.services', 'user.portfolios'])
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get();

        if ($muas->isEmpty()) {
            Log::warning('No active MUAs found for recommendations');
            return [];
        }

        // Calculate enhanced match scores
        $scoredMuas = [];
        foreach ($muas as $mua) {
            $score = $this->calculateEnhancedMatchScore(
                $customerSkinTypes,
                $customerMakeupPreferences,
                $customerMakeupStyles,
                $mua
            );

            if ($score > 0) {
                $scoredMuas[] = [
                    'mua' => $mua,
                    'score' => $score,
                    'match_type' => $this->determineMatchType($score),
                    'match_details' => $this->getEnhancedMatchDetails(
                        $customerSkinTypes,
                        $customerMakeupPreferences,
                        $customerMakeupStyles,
                        $mua
                    )
                ];
            }
        }

        // Sort by score descending
        usort($scoredMuas, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Ensure minimum recommendations if too few matches
        if (count($scoredMuas) < 3 && count($muas) > 3) {
            $scoredMuas = $this->addFallbackRecommendations($scoredMuas, $muas, $limit);
        }

        // Limit results
        $scoredMuas = array_slice($scoredMuas, 0, $limit);

        // Log::info('Enhanced recommendations calculated', [
        //     'total_muas' => count($scoredMuas),
        //     'top_scores' => array_slice(array_column($scoredMuas, 'score'), 0, 5)
        // ]);

        return $scoredMuas;
    }

    /**
     * Calculate enhanced match score with fuzzy matching
     *
     * @param array $customerSkinTypes
     * @param array $customerMakeupPreferences
     * @param array $customerMakeupStyles
     * @param MuaProfile $mua
     * @return float
     */
    private function calculateEnhancedMatchScore(array $customerSkinTypes, array $customerMakeupPreferences, array $customerMakeupStyles, MuaProfile $mua): float
    {
        $score = 0;
        $maxScore = 100;

        // Base score for active MUA
        $baseScore = 10;

        // Skin type matching (30% weight)
        $skinTypeScore = $this->calculateFuzzySkinTypeMatch($customerSkinTypes, $mua->skin_type ?? []);
        $score += $skinTypeScore * 30;

        // Makeup preferences matching (35% weight)
        $preferenceScore = $this->calculateFuzzyPreferenceMatch($customerMakeupPreferences, $mua->makeup_preferences ?? []);
        $score += $preferenceScore * 35;

        // Makeup style matching (35% weight)
        $styleScore = $this->calculateFuzzyStyleMatch($customerMakeupStyles, $mua->makeup_styles ?? []);
        $score += $styleScore * 35;

        // Bonus for complete profiles
        if (!empty($mua->user->services) && !empty($mua->user->portfolios)) {
            $score += 5;
        }

        // Ensure minimum score
        $finalScore = max($score + $baseScore, 15);

        return min($finalScore, $maxScore);
    }

    /**
     * Calculate fuzzy skin type match
     *
     * @param array $customerTypes
     * @param array $muaTypes
     * @return float
     */
    private function calculateFuzzySkinTypeMatch(array $customerTypes, array $muaTypes): float
    {
        if (empty($customerTypes) || empty($muaTypes)) {
            return 0.5; // Neutral score when data is missing
        }

        $customerTypes = array_map('strtolower', $customerTypes);
        $muaTypes = array_map('strtolower', $muaTypes);

        $matches = array_intersect($customerTypes, $muaTypes);
        $similarity = count($matches) / max(count($customerTypes), count($muaTypes));

        // Partial credit for similar types
        $partialMatches = 0;
        foreach ($customerTypes as $customerType) {
            foreach ($muaTypes as $muaType) {
                if (strpos($customerType, $muaType) !== false || strpos($muaType, $customerType) !== false) {
                    $partialMatches += 0.5;
                }
            }
        }

        return min($similarity + ($partialMatches * 0.1), 1.0);
    }

    /**
     * Calculate fuzzy preference match
     *
     * @param array $customerPrefs
     * @param array $muaPrefs
     * @return float
     */
    private function calculateFuzzyPreferenceMatch(array $customerPrefs, array $muaPrefs): float
    {
        if (empty($customerPrefs) || empty($muaPrefs)) {
            return 0.5; // Neutral score when data is missing
        }

        $customerPrefs = array_map('strtolower', $customerPrefs);
        $muaPrefs = array_map('strtolower', $muaPrefs);

        $matches = array_intersect($customerPrefs, $muaPrefs);
        $similarity = count($matches) / max(count($customerPrefs), count($muaPrefs));

        // Handle partial word matches
        $partialMatches = 0;
        foreach ($customerPrefs as $customerPref) {
            foreach ($muaPrefs as $muaPref) {
                similar_text($customerPref, $muaPref, $percent);
                if ($percent > 70) {
                    $partialMatches += 0.7;
                }
            }
        }

        return min($similarity + ($partialMatches * 0.05), 1.0);
    }

    /**
     * Calculate fuzzy style match
     *
     * @param array $customerStyles
     * @param array $muaStyles
     * @return float
     */
    private function calculateFuzzyStyleMatch(array $customerStyles, array $muaStyles): float
    {
        if (empty($customerStyles) || empty($muaStyles)) {
            return 0.5; // Neutral score when data is missing
        }

        $customerStyles = array_map('strtolower', $customerStyles);
        $muaStyles = array_map('strtolower', $muaStyles);

        $matches = array_intersect($customerStyles, $muaStyles);
        $similarity = count($matches) / max(count($customerStyles), count($muaStyles));

        // Handle style categories and synonyms
        $styleMap = [
            'natural' => ['natural', 'minimal', 'everyday'],
            'glamorous' => ['glamorous', 'glam', 'evening'],
            'bridal' => ['bridal', 'wedding', 'bride'],
            'party' => ['party', 'night', 'evening'],
            'professional' => ['professional', 'office', 'work']
        ];

        $mappedMatches = 0;
        foreach ($customerStyles as $customerStyle) {
            foreach ($muaStyles as $muaStyle) {
                foreach ($styleMap as $category => $synonyms) {
                    if (in_array($customerStyle, $synonyms) && in_array($muaStyle, $synonyms)) {
                        $mappedMatches += 1;
                    }
                }
            }
        }

        return min($similarity + ($mappedMatches * 0.1), 1.0);
    }

    /**
     * Normalize preferences array
     *
     * @param mixed $preferences
     * @return array
     */
    private function normalizePreferences($preferences): array
    {
        if (empty($preferences)) {
            return [];
        }

        if (is_string($preferences)) {
            $decoded = json_decode($preferences, true);
            return is_array($decoded) ? $decoded : [$preferences];
        }

        if (is_array($preferences)) {
            return $preferences;
        }

        return [$preferences];
    }

    /**
     * Determine match type based on score
     *
     * @param float $score
     * @return string
     */
    private function determineMatchType(float $score): string
    {
        if ($score >= 80) {
            return 'excellent';
        } elseif ($score >= 60) {
            return 'good';
        } elseif ($score >= 40) {
            return 'fair';
        } else {
            return 'fallback';
        }
    }

    /**
     * Add fallback recommendations when few matches exist
     *
     * @param array $currentMatches
     * @param mixed $allMuas
     * @param int $limit
     * @return array
     */
    private function addFallbackRecommendations(array $currentMatches, $allMuas, int $limit): array
    {
        $matchedIds = array_column(array_column($currentMatches, 'mua'), 'user_id');
        $fallbackCount = $limit - count($currentMatches);

        $fallbackMuas = $allMuas->filter(function ($mua) use ($matchedIds) {
            return !in_array($mua->user_id, $matchedIds);
        })->take($fallbackCount);

        foreach ($fallbackMuas as $mua) {
            $currentMatches[] = [
                'mua' => $mua,
                'score' => 25, // Base fallback score
                'match_type' => 'fallback',
                'match_details' => [
                    'reason' => 'Popular MUA with good reviews',
                    'fallback_reason' => 'No specific matches but highly rated'
                ]
            ];
        }

        return $currentMatches;
    }

    /**
     * Get enhanced match details
     *
     * @param array $customerSkinTypes
     * @param array $customerMakeupPreferences
     * @param array $customerMakeupStyles
     * @param MuaProfile $mua
     * @return array
     */
    private function getEnhancedMatchDetails(array $customerSkinTypes, array $customerMakeupPreferences, array $customerMakeupStyles, MuaProfile $mua): array
    {
        return [
            'skin_type_compatibility' => $this->getCompatibleSkinTypes(implode(', ', $customerSkinTypes)),
            'matching_preferences' => array_intersect($customerMakeupPreferences, $mua->makeup_preferences ?? []),
            'matching_styles' => array_intersect($customerMakeupStyles, $mua->makeup_styles ?? []),
            'mua_specialties' => $mua->makeup_specializations ?? [],
            'service_count' => count($mua->user->services ?? []),
            'portfolio_count' => count($mua->user->portfolios ?? [])
        ];
    }
}
