<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\MuaProfile;
use App\Models\CustomerProfile;
use App\Models\Service;
use App\Models\Booking;

class MigrateImagesToS3 extends Command
{
    protected $signature = 'images:migrate-to-s3';
    protected $description = 'Migrate all local images to S3 Supabase storage';

    public function handle()
    {
        $this->info('Starting image migration to S3...');
        
        // Migrate profile photos
        $this->migrateProfilePhotos();
        
        // Migrate service photos
        $this->migrateServicePhotos();
        
        // Migrate portfolio images
        $this->migratePortfolioImages();
        
        // Migrate payment proofs
        $this->migratePaymentProofs();
        
        $this->info('Image migration completed!');
    }

    private function migrateProfilePhotos()
    {
        $this->info('Migrating profile photos...');
        
        // MUA profiles
        $muaProfiles = MuaProfile::whereNotNull('profile_photo')->get();
        foreach ($muaProfiles as $profile) {
            $this->migrateSingleImage(
                'profile_photos/' . $profile->profile_photo,
                'images/profile_photos/' . $profile->profile_photo
            );
        }
        
        // Customer profiles
        $customerProfiles = CustomerProfile::whereNotNull('profile_photo')->get();
        foreach ($customerProfiles as $profile) {
            $this->migrateSingleImage(
                'profile_photos/' . $profile->profile_photo,
                'images/profile_photos/' . $profile->profile_photo
            );
        }
    }

    private function migrateServicePhotos()
    {
        $this->info('Migrating service photos...');
        
        $services = Service::whereNotNull('photo')->get();
        foreach ($services as $service) {
            $this->migrateSingleImage(
                'service_photos/' . $service->photo,
                'images/service_photos/' . $service->photo
            );
        }
    }

    private function migratePortfolioImages()
    {
        $this->info('Migrating portfolio images...');
        
        // This would need to be adapted based on your portfolio structure
        // Assuming portfolio images are stored in a separate table or field
    }

    private function migratePaymentProofs()
    {
        $this->info('Migrating payment proofs...');
        
        $bookings = Booking::whereNotNull('payment_proof')->get();
        foreach ($bookings as $booking) {
            $this->migrateSingleImage(
                $booking->payment_proof,
                'images/' . $booking->payment_proof
            );
        }
    }

    private function migrateSingleImage($localPath, $s3Path)
    {
        if (Storage::disk('public')->exists($localPath)) {
            $contents = Storage::disk('public')->get($localPath);
            Storage::disk('s3')->put($s3Path, $contents);
            $this->info("Migrated: {$localPath} -> {$s3Path}");
        }
    }
}
