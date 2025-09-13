<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    /**
     * Display a listing of guests (with optional search and pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Guest::query();

        // Search by name, email, or phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Pagination
        $guests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => GuestResource::collection($guests),
            'meta' => [
                'current_page' => $guests->currentPage(),
                'last_page' => $guests->lastPage(),
                'per_page' => $guests->perPage(),
                'total' => $guests->total(),
            ],
        ]);
    }

    /**
     * Store a newly created guest in storage.
     */
    public function store(StoreGuestRequest $request): JsonResponse
    {
        $guest = Guest::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Guest created successfully.',
            'data' => new GuestResource($guest),
        ], 201);
    }

    /**
     * Display the specified guest.
     */
    public function show(Guest $guest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new GuestResource($guest),
        ]);
    }

    /**
     * Update the specified guest.
     */
    public function update(UpdateGuestRequest $request, Guest $guest): JsonResponse
    {
        $guest->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Guest updated successfully.',
            'data' => new GuestResource($guest),
        ]);
    }

    /**
     * Remove the specified guest from storage.
     */
    public function destroy(Guest $guest): JsonResponse
    {
        $guest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guest deleted successfully.',
        ]);
    }
}
