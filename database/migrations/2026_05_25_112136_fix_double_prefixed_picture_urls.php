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
        // Fix double-prefixed picture URLs
        // From: http://localhost/storage/http://localhost/storage/profiles/filename.jpg
        // To: profiles/filename.jpg
        
        DB::table('users')
            ->whereNotNull('picture')
            ->get()
            ->each(function ($user) {
                if ($user->picture && str_contains($user->picture, 'http')) {
                    // Extract the relative path from the URL
                    $relativePath = $this->extractRelativePath($user->picture);
                    
                    if ($relativePath) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['picture' => $relativePath]);
                            
                        echo "Fixed user {$user->id}: {$relativePath}\n";
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
     * Extract relative path from a full URL
     */
    private function extractRelativePath($url)
    {
        // Try to extract path after /storage/
        if (preg_match('/\/storage\/(.+)$/', $url, $matches)) {
            $path = $matches[1];
            
            // If it still contains /storage/, extract everything after the last /storage/
            if (str_contains($path, '/storage/')) {
                $parts = explode('/storage/', $path);
                $path = end($parts);
            }
            
            return $path;
        }
        
        return null;
    }
};
