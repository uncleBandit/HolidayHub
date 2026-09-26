<?php

namespace App\Modules\Catalog\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Catalog\Presentation\Http\Requests\StoreOfferRequest;
use App\Modules\Catalog\Presentation\Http\Requests\UpdateOfferRequest;
use App\Modules\Catalog\Presentation\Http\Resources\OfferResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * List all offers with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Offer::query();

        // Optional filters: destination, active, price range, etc.
        if ($request->has('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }

        if ($request->has('active')) {
            $query->where('active', (bool) $request->active);
        }

        $offers = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => OfferResource::collection($offers),
            'meta' => [
                'total' => $offers->total(),
                'per_page' => $offers->perPage(),
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new offer.
     */
    public function store(StoreOfferRequest $request): JsonResponse
    {
        $offer = Offer::create($request->validated());

        return response()->json([
            'data' => new OfferResource($offer),
            'message' => 'Offer created successfully.',
        ], 201);
    }

    /**
     * Show a single offer.
     */
    public function show(Offer $offer): JsonResponse
    {
        return response()->json([
            'data' => new OfferResource($offer),
        ]);
    }

    /**
     * Update an offer.
     */
    public function update(UpdateOfferRequest $request, Offer $offer): JsonResponse
    {
        $offer->update($request->validated());

        return response()->json([
            'data' => new OfferResource($offer),
            'message' => 'Offer updated successfully.',
        ]);
    }

    /**
     * Delete an offer.
     */
    public function destroy(Offer $offer): JsonResponse
    {
        $offer->delete();

        return response()->json([
            'message' => 'Offer deleted successfully.',
        ], 204);
    }
}
