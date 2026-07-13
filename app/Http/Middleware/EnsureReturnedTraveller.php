<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReturnedTraveller
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (!$user->is_returned_traveller && $user->role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Returned Traveller status required.'
            ], 403);
        }

        return $next($request);
    }
}
