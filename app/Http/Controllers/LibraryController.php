<?php

namespace App\Http\Controllers;

use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LibraryController extends Controller
{
    /**
     * Get all libraries (for customers to view)
     */
    public function index()
    {
        try {
            $libraries = Library::get();
            return response()->json([
                'success' => true,
                'data' => $libraries
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get single library by ID
     */
    public function show($id)
    {
        try {
            $library = Library::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $library
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Library not found'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Create new library (admin only)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'author' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:100',
                'type' => 'required|in:audio,video,pdf,text',
                'duration' => 'nullable|string',
                'image_url' => 'nullable|string',
                'file_url' => 'nullable|string',
            ]);

        

            $library = Library::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Library created successfully',
                'data' => $library
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update library (admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            $library = Library::findOrFail($id);

            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'author' => 'nullable|string|max:255',
                'category' => 'nullable|string|max:100',
                'type' => 'nullable|in:audio,video,pdf,text',
                'duration' => 'nullable|string',
                'image_url' => 'nullable|string',
                'file_url' => 'nullable|string',
            ]);

            $library->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Library updated successfully',
                'data' => $library
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Library not found'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete library (admin only)
     */
    public function destroy($id)
    {
        try {
            $library = Library::findOrFail($id);
            $library->delete();

            return response()->json([
                'success' => true,
                'message' => 'Library deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Library not found'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Get all libraries for admin dashboard
     */
    public function adminList()
    {
        try {
            $libraries = Library::orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $libraries
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
