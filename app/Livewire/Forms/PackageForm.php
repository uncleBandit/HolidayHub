<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Rule;
use App\Models\Package;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class PackageForm extends Form
{
    // A public property to hold the Package model instance, used for editing.
    public ?Package $package = null;

    // Form fields with Livewire 3 #[Rule] attributes for validation.
    // The rules now include new fields from the Package model.
    #[Rule('required|string|max:255')]
    public string $title = '';

    // The slug will be generated automatically, but we keep it as a property
    public string $slug = '';

    #[Rule('required|exists:destinations,id')]
    public ?int $destination_id = null;

    #[Rule('required|numeric|min:0')]
    public ?float $price = null;

    // The currency is now managed by the form
    #[Rule('required|string|in:USD,EUR,GBP')]
    public string $currency = 'USD';

    #[Rule('required|integer|min:1')]
    public ?int $duration_days = null;

    #[Rule('nullable|string')]
    public string $description = '';

    #[Rule('required|integer|min:1')]
    public ?int $max_guests = null;

    // These properties match the model's naming convention for dates
    #[Rule('required|date')]
    public string $start_date = '';

    #[Rule('required|date|after_or_equal:start_date')]
    public string $end_date = '';

    // A property to handle file uploads
    #[Rule('nullable|image|max:2048')]
    public $image = null;

    // A property to hold the path to the current image when editing
    public ?string $currentImagePath = null;

    #[Rule('boolean')]
    public bool $is_featured = false;

    // The status of the package (draft, published, archived)
    #[Rule('required|string|in:draft,published,archived')]
    public string $status = 'draft';

    /**
     * Set the form properties for an existing package.
     * If no package is provided, it resets the form for creation.
     */
    public function setPackage(?Package $package = null): void
    {
        $this->package = $package;

        // Populate form fields from the model
        $this->title = $package->title ?? '';
        $this->slug = $package->slug ?? '';
        $this->destination_id = $package->destination_id ?? null;
        $this->price = $package->price ?? null;
        $this->currency = $package->currency ?? 'USD';
        $this->duration_days = $package->duration_days ?? null;
        $this->description = $package->description ?? '';
        $this->max_guests = $package->max_guests ?? null;
        $this->start_date = $package->start_date ?? '';
        $this->end_date = $package->end_date ?? '';
        $this->currentImagePath = $package->image_url ?? null;
        $this->is_featured = $package->is_featured ?? false;
        $this->status = $package->status ?? 'draft';

        // Reset the image upload field to avoid validation issues
        $this->image = null;
    }

    /**
     * Handles the creation or update logic based on the presence of a package model.
     */
    public function save(): void
    {
        $this->validate();

        $data = $this->all();

        // Automatically generate a slug from the title
        $data['slug'] = Str::slug($this->title);

        // Handle image upload logic
        if ($this->image) {
            // Store the new image in the 'public/packages' directory
            $imagePath = $this->image->store('packages', 'public');
            $data['image_url'] = $imagePath;

            // If we are editing and a new image is uploaded, delete the old one
            if ($this->package && $this->package->image_url) {
                Storage::disk('public')->delete($this->package->image_url);
            }
        } else {
            // If no new image is uploaded, keep the existing one
            $data['image_url'] = $this->currentImagePath;
        }

        // Remove the temporary image property before saving to the database
        unset($data['image']);
        unset($data['currentImagePath']);

        // Create or update the package
        if ($this->package) {
            $this->package->update($data);
        } else {
            // Add the provider_id and agent_id when creating a new package
            $data['provider_id'] = Auth::user()->provider->id;
            // The agent_id will be the authenticated user's ID
            $data['agent_id'] = Auth::id();
            Package::create($data);
        }

        $this->reset();
    }
}
