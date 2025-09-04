<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the packages with search, filtering, and sorting.
     */
    public function index(Request $request)
    {
        $query = Package::query();

        // Search by title or destination
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Filter by price range
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by tags
        if ($tags = $request->input('tags')) {
            $query->whereJsonContains('tags', $tags);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Sorting (default: newest)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $packages = $query->paginate(12)->withQueryString();

        return view('packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new package.
     */
    public function create()
    {
        return view('packages.create');
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(StorePackageRequest $request)
    {
        $package = Package::create($request->validated());

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package created successfully!');
    }

    /**
     * Display the specified package.
     */
    public function show(Package $package)
    {
        return view('packages.show', compact('package'));
    }

    /**
     * Show the form for editing the specified package.
     */
    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    /**
     * Update the specified package in storage.
     */
    public function update(UpdatePackageRequest $request, Package $package)
    {
        $package->update($request->validated());

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package updated successfully!');
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()
            ->route('packages.index')
            ->with('success', 'Package deleted successfully!');
    }
}
