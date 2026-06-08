<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustodianController extends Controller
{
    /**
     * Get all custodians with pagination (for customer portal)
     */
    public function getAll(Request $request)
    {
        try {
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 12);
            $search = $request->query('search', '');
            $country = $request->query('country', '');
            $specialty = $request->query('specialty', '');

            $query = User::where('role', 'custodian')
                ->where(function ($q) {
                    $q->where('status', 'active');
                })
                ->withCount('bookings as sessions');

            // Search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('specialty', 'like', "%{$search}%");
                });
            }

            // Country filter
            if ($country && $country !== 'All countries') {
                $query->where('country', $country);
            }

            // Specialty filter
            if ($specialty && $specialty !== 'All specialties') {
                $query->where('specialty', $specialty);
            }

            $total = $query->count();
            $custodians = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'custodians' => $custodians->items(),
                'total' => $total,
                'currentPage' => $page,
                'totalPages' => ceil($total / $limit),
                'perPage' => $limit,
            ]);
        } catch (\Exception $e) {
            Log::error('CustodianController::getAll - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch custodians'], 500);
        }
    }

    /**
     * Get a single custodian by ID (for customer portal)
     */
    public function getOne(Request $request, $id)
    {
        try {
            $custodian = User::where('role', 'custodian')
                ->where(function ($q) {
                    $q->where('status', 'active')->orWhereNull('status');
                })
                ->withCount('bookings as sessions')
                ->findOrFail($id);
            return response()->json(['custodian' => $custodian]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Custodian not found'], 404);
        } catch (\Exception $e) {
            Log::error('CustodianController::getOne - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch custodian'], 500);
        }
    }

    public function apply(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'location' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'years_experience' => 'required|integer|min:0',
                'specialty' => 'required|string|max:255',
                'availability' => 'required|in:Available,Booked',
                'description' => 'required|string',
                'certification' => 'nullable|string',
                'coc_status' => 'nullable|string',
                'short_bio' => 'required|string',
                'about' => 'required|string',
                'whatsapp' => 'nullable|string',
                'instagram' => 'nullable|string',
                'linkedin' => 'nullable|string',
                'languages' => 'required|array|min:1',
                'services' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Check if user is authenticated
            $user = $request->user();
            
            if ($user) {
                // Update existing user
                $user->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => 'custodian',
                    'status' => 'pending',
                    'location' => $request->location,
                    'country' => $request->country,
                    'years_experience' => $request->years_experience,
                    'specialty' => $request->specialty,
                    'availability' => $request->availability,
                    'description' => $request->description,
                    'certification' => $request->certification,
                    'coc_status' => $request->coc_status,
                    'short_bio' => $request->short_bio,
                    'about' => $request->about,
                    'whatsapp' => $request->whatsapp,
                    'instagram' => $request->instagram,
                    'linkedin' => $request->linkedin,
                    'languages' => $request->input('languages', []),
                    'services' => $request->services ?? $user->services ?? [],
                ]);
            } else {
                $password = Str::random(16);
                // Create new user with temporary password
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($password), // Temporary password
                    'role' => 'custodian',
                    'status' => 'pending',
                    'location' => $request->location,
                    'country' => $request->country,
                    'years_experience' => $request->years_experience,
                    'specialty' => $request->specialty,
                    'availability' => $request->availability,
                    'description' => $request->description,
                    'certification' => $request->certification,
                    'coc_status' => $request->coc_status,
                    'short_bio' => $request->short_bio,
                    'about' => $request->about,
                    'whatsapp' => $request->whatsapp,
                    'instagram' => $request->instagram,
                    'linkedin' => $request->linkedin,
                    'languages' => $request->input('languages', []),
                    'services' => $request->services ?? [],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Custodian application submitted',
            ], 201);
        } catch (\Exception $e) {
            Log::error('CustodianController::apply - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit application'], 500);
        }
    }

    /**
     * Get custodians for admin with pagination
     */
    public function getForAdmin(Request $request)
    {
        try {
        
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);
            $search = $request->query('search', '');

            $query = User::where('role', 'custodian')->withCount('bookings as sessions');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $custodians = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'custodians' => $custodians->items(),
                'total' => $total,
                'currentPage' => $page,
                'totalPages' => ceil($total / $limit),
                'perPage' => $limit,
            ]);
        } catch (\Exception $e) {
            Log::error('CustodianController::getForAdmin - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch custodians'], 500);
        }
    }

    /**
     * Get a single custodian for admin
     */
    public function show(Request $request, $id)
    {
        try {
            $custodian = User::where('role', 'custodian')
                ->withCount('bookings as sessions')
                ->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $custodian,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Custodian not found'], 404);
        } catch (\Exception $e) {
            Log::error('CustodianController::show - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch custodian'], 500);
        }
    }

    /**
     * Create custodian
     */
    public function store(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:users,name',
                'email' => 'required|email|unique:users,email',
                'location' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'years_experience' => 'required|integer|min:0',
                'specialty' => 'required|string|max:255',
                'availability' => 'required|in:Available,Booked',
                'description' => 'required|string',
                'status' => 'nullable|in:active,inactive,suspended,pending',
                'certification' => 'nullable|string',
                'coc_status' => 'nullable|string',
                'review_avg' => 'nullable|numeric',
                'sessions_count' => 'nullable|integer',
                'short_bio' => 'nullable|string',
                'about' => 'nullable|string',
                'whatsapp' => 'nullable|string',
                'instagram' => 'nullable|string',
                'linkedin' => 'nullable|string',
                'languages' => 'nullable|array',
                'services' => 'nullable|array',
                'testimonials' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
$password = Str::random(16);
            $custodian = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $password,
                'role' => 'custodian',
                'status' => $request->status ?? 'active',
                'location' => $request->location,
                'country' => $request->country,
                'years_experience' => $request->years_experience,
                'specialty' => $request->specialty,
                'availability' => $request->availability,
                'description' => $request->description,
                'tags' => $request->tags ?? [],
                'price_from' => 0,
                'certification' => $request->certification,
                'coc_status' => $request->coc_status,
                'review_avg' => 0,
                'sessions_count' => $request->sessions_count ?? 0,
                'short_bio' => $request->short_bio ?? null,
                'about' => $request->about ?? null,
                'whatsapp' => $request->whatsapp ?? null,
                'instagram' => $request->instagram ?? null,
                'linkedin' => $request->linkedin ?? null,
                'languages' => $request->input('languages', []),
                'services' => $request->services ?? [],
                'testimonials' => $request->testimonials ?? [],
            ]);

            return response()->json([
                'success' => true,
                'data' => $custodian,
                'message' => 'Custodian created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('CustodianController::store - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create custodian: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update custodian
     */
    public function update(Request $request, $id)
    {
        try {
            $custodian = User::where('role', 'custodian')->find($id);
            if (!$custodian) {
                return response()->json(['error' => 'Custodian not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255|unique:users,name,' . $id,
                'email' => 'email|unique:users,email,' . $id,
                'location' => 'string|max:255',
                'country' => 'string|max:255',
                'years_experience' => 'integer|min:0',
                'specialty' => 'string|max:255',
                'availability' => 'in:Available,Booked',
                'description' => 'string',
                'price_from' => 'numeric|min:0',
                'status' => 'in:active,inactive,suspended,pending',
                'certification' => 'nullable|string',
                'coc_status' => 'nullable|string',
                'review_avg' => 'nullable|numeric',
                'sessions_count' => 'nullable|integer',
                'short_bio' => 'nullable|string',
                'about' => 'nullable|string',
                'whatsapp' => 'nullable|string',
                'instagram' => 'nullable|string',
                'linkedin' => 'nullable|string',
                'languages' => 'nullable|array',
                'services' => 'nullable|array',
                'testimonials' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Build update data - only update fields that are in fillable array
            $updateData = [];
            $fillableFields = [
                'name', 'email', 'location', 'country', 'years_experience', 'specialty',
                'availability', 'description', 'status', 'certification', 'coc_status',
                'review_avg', 'sessions_count', 'short_bio', 'about', 'languages',
                'services', 'testimonials', 'price_from', 'tags', 'whatsapp', 'instagram', 'linkedin'
            ];

            foreach ($fillableFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            $custodian->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $custodian,
                'message' => 'Custodian updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('CustodianController::update - Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update custodian: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete custodian
     */
    public function destroy(Request $request, $id)
    {
        try {
           

            $custodian = User::where('role', 'custodian')->find($id);
            if (!$custodian) {
                return response()->json(['error' => 'Custodian not found'], 404);
            }

            $custodian->delete();

            return response()->json([
                'success' => true,
                'message' => 'Custodian deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('CustodianController::destroy - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete custodian'], 500);
        }
    }
}
