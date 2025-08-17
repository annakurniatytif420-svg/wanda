<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MoveServicePhotos extends Command
{
    protected $signature = 'photos:move-to-public';
    protected $description = 'Move service photos from private to public storage';

    public function handle()
    {
        $source = storage_path('app/private/public/service_photos');
        $destination = storage_path('app/public/service_photos');

        if (!File::exists($source)) {
            $this->error("Source folder not found: $source");
            return 1;
        }

        File::ensureDirectoryExists($destination);

        $files = File::files($source);

        if (empty($files)) {
            $this->info("No files found to move.");
            return 0;
        }

        foreach ($files as $file) {
            $targetPath = $destination . '/' . $file->getFilename();

            File::move($file->getPathname(), $targetPath);
            $this->info("Moved: {$file->getFilename()}");
        }

        $this->info('✅ All files moved successfully.');
        return 0;
    }
}
