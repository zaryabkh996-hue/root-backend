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
        // Standardize all picture paths to relative format: profiles/filename.jpg
        DB::table('users')
            ->whereNotNull('picture')
            ->get(['id', 'picture'])
            ->each(function ($user) {
                if ($user->picture && $user->picture !== '') {
                    $cleanPath = $this->cleanPath($user->picture);
                    if ($cleanPath !== $user->picture) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['picture' => $cleanPath]);
                        echo "Fixed user {$user->id}: {$cleanPath}\n";
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
     * Clean picture path to relative format
     */
    private function cleanPath($path)
    {
        if (!$path) return null;
        
        // Extract filename from any format
        // From: /storage/profiles/filename.jpg
        // From: http://localhost/storage/profiles/filename.jpg
        // To: profiles/filename.jpg
        
        if (preg_match('/\/storage\/(.*?)$/', $path, $matches)) {
            return $matches[1];
        }
        
        // Already in relative format
        if (strpos($path, '/') === 0 || strpos($path, 'http') !== 0) {
            if (strpos($path, '/') === 0) {
                return ltrim($path, '/');
            }
            return $path;
        }
        
        return $path;
    }
};
