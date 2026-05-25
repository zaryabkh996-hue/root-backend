<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Standardize all journey photo paths to relative format: journey-photos/filename.jpg
        DB::table('journey_photos')
            ->whereNotNull('url')
            ->get(['id', 'url'])
            ->each(function ($photo) {
                if ($photo->url && $photo->url !== '') {
                    $cleanPath = $this->cleanPath($photo->url);
                    if ($cleanPath !== $photo->url) {
                        DB::table('journey_photos')
                            ->where('id', $photo->id)
                            ->update(['url' => $cleanPath]);
                        echo "Fixed photo {$photo->id}: {$cleanPath}\n";
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need a down - it just fixes data
    }

    /**
     * Clean photo path to relative format
     */
    private function cleanPath($path)
    {
        if (!$path) return null;
        
        // Extract relative path from any format
        // From: /storage/journey-photos/filename.jpg
        // From: http://localhost/storage/journey-photos/filename.jpg
        // To: journey-photos/filename.jpg
        
        if (preg_match('/\/storage\/(.*?)$/', $path, $matches)) {
            return $matches[1];
        }
        
        // Already in relative format
        if (strpos($path, 'http') === false && strpos($path, '/') === 0) {
            return ltrim($path, '/');
        }
        
        return $path;
    }
};
