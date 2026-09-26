<?php

namespace App\Modules\Media\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Media\Domain\Models\Image;
use App\Modules\Media\Presentation\Http\Requests\UpdateImageRequest;
use App\Modules\Media\Presentation\Http\Resources\ImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * List all images, optionally filtered by related model.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Image::query();

        if ($request->has('imageable_type') && $request->has('imageable_id')) {
            $query->where('imageable_type', $request->imageable_type)
                ->where('imageable_id', $request->imageable_id);
        }

        $images = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => ImageResource::collection($images),
            'meta' => [
                'total' => $images->total(),
                'per_page' => $images->perPage(),
                'current_page' => $images->currentPage(),
                'last_page' => $images->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new image.
     */
    public function store(StoreImageReques $request): JsonResponse
    {
        $filePath = $request->file('file')->store('images', 'public');

        $image = Image::create([
            'file_path' => $filePath,
            'imageable_type' => $request->imageable_type,
            'imageable_id' => $request->imageable_id,
            'meta' => $request->get('meta', []),
        ]);

        return response()->json([
            'data' => new ImageResource($image),
            'message' => 'Image uploaded successfully.',
        ], 201);
    }

    /**
     * Show a single image.
     */
    public function show(Image $image): JsonResponse
    {
        return response()->json([
            'data' => new ImageResource($image),
        ]);
    }

    /**
     * Update image metadata.
     */
    public function update(UpdateImageRequest $request, Image $image): JsonResponse
    {
        $image->update($request->validated());

        return response()->json([
            'data' => new ImageResource($image),
            'message' => 'Image updated successfully.',
        ]);
    }

    /**
     * Delete an image.
     */
    public function destroy(Image $image): JsonResponse
    {
        Storage::disk('public')->delete($image->file_path);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ], 204);
    }
}
