<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudinaryController extends Controller
{
    /**
     * Generate signature for signed upload
     */
    public function signature(Request $request)
    {
        $timestamp = time();
        $apiSecret = env('CLOUDINARY_API_SECRET', 'mock_secret');
        $apiKey = env('CLOUDINARY_API_KEY', 'mock_key');
        
        $uploadPreset = $request->input('upload_preset') ?? env('NEXT_PUBLIC_CLOUDINARY_UPLOAD_PRESET', 'ourroots_upload');
        
        // Build signing parameters (must be sorted alphabetically)
        $params = [
            'timestamp'     => $timestamp,
            'upload_preset' => $uploadPreset,
        ];
        
        ksort($params);
        
        $paramString = "";
        foreach ($params as $key => $val) {
            $paramString .= "{$key}={$val}&";
        }
        $paramString = rtrim($paramString, "&");
        
        // Compute SHA-1 signature
        $signature = sha1($paramString . $apiSecret);
        
        return response()->json([
            'success'       => true,
            'signature'     => $signature,
            'timestamp'     => $timestamp,
            'api_key'       => $apiKey,
            'upload_preset' => $uploadPreset,
        ]);
    }

    /**
     * Generate time-limited signed delivery URL for private/authenticated assets
     */
    public function deliveryUrl(Request $request)
    {
        $request->validate([
            'public_id'     => 'required|string',
            'resource_type' => 'nullable|string|in:image,video,raw',
        ]);

        $cloudName    = env('CLOUDINARY_CLOUD_NAME') ?? env('NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME', 'iqqprnqm');
        $apiSecret    = env('CLOUDINARY_API_SECRET', 'mock_secret');
        $publicId     = $request->input('public_id');
        $resourceType = $request->input('resource_type', 'video');

        // Expiration (1 hour from now)
        $expire = time() + 3600;

        // Build token signature based on public ID, timestamp, and API secret
        $toSign    = "public_id={$publicId}&timestamp={$expire}";
        $signature = sha1($toSign . $apiSecret);

        // Standard Cloudinary authenticated delivery pattern
        $signedUrl = "https://res.cloudinary.com/{$cloudName}/{$resourceType}/authenticated/s--{$signature}--/v1/{$publicId}?expires={$expire}";

        return response()->json([
            'success' => true,
            'url'     => $signedUrl,
        ]);
    }
}
