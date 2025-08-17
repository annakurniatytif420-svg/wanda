<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    protected $disk = 's3';

    public function uploadProfilePhoto(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'profile_photos', $oldPhoto);
    }

    public function uploadServicePhoto(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'service_photos', $oldPhoto);
    }

    public function uploadPortfolioImage(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'portfolio', $oldPhoto);
    }

    public function uploadPaymentProof(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'payment_proofs', $oldPhoto);
    }

    private function uploadImage(UploadedFile $file, $folder, $oldPhoto = null)
    {
        // Validate AWS configuration
        $this->validateS3Configuration();

        // Delete old photo if exists
        if ($oldPhoto) {
            $this->deleteImage($oldPhoto, $folder);
        }

        // Generate unique filename
        $filename = $folder . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        try {
            // Store file
            $path = $file->storeAs($folder, $filename, $this->disk);
            return basename($path);
        } catch (\Exception $e) {
            throw new \Exception("Failed to upload image to S3: " . $e->getMessage());
        }
    }

    public function deleteImage($filename, $folder = null)
    {
        if (!$filename) return;

        if ($folder) {
            $path = $folder . '/' . $filename;
        } else {
            // Try to determine folder from filename
            $path = $this->determinePath($filename);
        }

        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    public function getImageUrl($filename, $folder = null)
    {
        if (!$filename) return null;

        if ($folder) {
            $path = $folder . '/' . $filename;
        } else {
            $path = $this->determinePath($filename);
        }

        // Get the full URL from S3/Supabase
        $url = Storage::disk($this->disk)->url($path);

        // Ensure the URL is absolute and properly formatted for Supabase
        if (strpos($url, 'http') !== 0) {
            $url = rtrim(env('AWS_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/'), '/') . '/' . ltrim($path, '/');
        }

        return $url;
    }

    private function determinePath($filename)
    {
        // Determine folder based on filename patterns
        if (strpos($filename, 'profile_') === 0) {
            return 'profile_photos/' . $filename;
        }

        if (strpos($filename, 'service_') === 0) {
            return 'service_photos/' . $filename;
        }

        if (strpos($filename, 'portfolio_') === 0) {
            return 'portfolio/' . $filename;
        }

        if (strpos($filename, 'payment_') === 0) {
            return 'payment_proofs/' . $filename;
        }

        return '/' . $filename;
    }

    /**
     * Validate S3 configuration
     *
     * @throws Exception
     */
    private function validateS3Configuration()
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $key = config('filesystems.disks.s3.key');
        $secret = config('filesystems.disks.s3.secret');
        $region = config('filesystems.disks.s3.region');

        if (empty($bucket)) {
            throw new \Exception('AWS_BUCKET environment variable is not set or empty. Please configure your AWS S3 bucket name.');
        }

        if (empty($key)) {
            throw new \Exception('AWS_ACCESS_KEY_ID environment variable is not set or empty.');
        }

        if (empty($secret)) {
            throw new \Exception('AWS_SECRET_ACCESS_KEY environment variable is not set or empty.');
        }

        if (empty($region)) {
            throw new \Exception('AWS_DEFAULT_REGION environment variable is not set or empty.');
        }
    }
}
