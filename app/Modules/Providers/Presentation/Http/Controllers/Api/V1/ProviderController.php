<?php

namespace App\Modules\Providers\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Providers\Presentation\Http\Requests\StoreProviderRequest;
use App\Modules\Providers\Presentation\Http\Requests\UpdateProviderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    /**
     * Display a listing of providers.
     */
    public function index(Request $request): JsonResponse
    {
        $providers = Provider::query()
            ->when($request->input('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate(15);

        return response()->json($providers);
    }

    /**
     * Store a newly created provider.
     */
    public function store(StoreProviderRequest $request): JsonResponse
    {
        $provider = Provider::create($request->validated());

        return response()->json([
            'data' => $provider,
            'message' => 'Provider created successfully.',
        ], 201);
    }

    /**
     * Display the specified provider.
     */
    public function show(Provider $provider): JsonResponse
    {
        return response()->json([
            'data' => $provider,
        ]);
    }

    /**
     * Update the specified provider.
     */
    public function update(UpdateProviderRequest $request, Provider $provider): JsonResponse
    {
        $provider->update($request->validated());

        return response()->json([
            'data' => $provider,
            'message' => 'Provider updated successfully.',
        ]);
    }

    /**
     * Remove the specified provider.
     */
    public function destroy(Provider $provider): JsonResponse
    {
        $provider->delete();

        return response()->json([
            'message' => 'Provider deleted successfully.',
        ], 204);
    }
}
