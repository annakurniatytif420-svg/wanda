<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Test customer recommendations
try {
    // Get a customer user
    $customer = \App\Models\User::where('role', 'customer')->first();
    
    if (!$customer) {
        echo "No customer found. Please create a customer user first.\n";
        exit;
    }

    // Get customer profile
    $profile = $customer->customerProfile;
    
    if (!$profile) {
        echo "Customer profile not found. Creating test profile...\n";
        $profile = \App\Models\CustomerProfile::create([
            'user_id' => $customer->id,
            'skin_type' => json_encode(['normal', 'combination']),
            'makeup_preferences' => json_encode(['natural', 'bridal']),
            'makeup_style' => json_encode(['natural', 'elegant'])
        ]);
    }

    // Test enhanced recommendation service
    $service = new \App\Services\EnhancedRecommendationService();
    $recommendations = $service->getRecommendations($profile, 5);

    echo "=== Customer Recommendations Test ===\n";
    echo "Customer ID: {$customer->id}\n";
    echo "Customer Name: {$customer->name}\n";
    echo "Skin Types: " . implode(', ', json_decode($profile->skin_type, true) ?? []) . "\n";
    echo "Makeup Preferences: " . implode(', ', json_decode($profile->makeup_preferences, true) ?? []) . "\n";
    echo "Makeup Styles: " . implode(', ', json_decode($profile->makeup_style, true) ?? []) . "\n\n";

    echo "=== Recommendations ===\n";
    echo "Total recommendations: " . count($recommendations) . "\n\n";

    foreach ($recommendations as $index => $rec) {
        $mua = $rec['mua'];
        $user = $mua->user;
        
        echo ($index + 1) . ". {$user->name}\n";
        echo "   Score: {$rec['score']}/100\n";
        echo "   Match Type: {$rec['match_type']}\n";
        echo "   Starting Price: Rp " . number_format($rec['starting_price'] ?? 0) . "\n";
        echo "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
