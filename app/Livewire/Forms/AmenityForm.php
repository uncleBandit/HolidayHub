<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Rule;
use App\Models\Amenity;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class AmenityForm extends Form
{
    // A public property to hold the Amenity model instance, used for editing.
    public ?Amenity $amenity = null;

    // Form fields with Livewire 3 #[Rule] attributes for validation.
    // The rules have been expanded to include the 'image' and 'type' fields.
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string')]
    public string $description = '';

    #[Rule('required|string|in:hotel,package,room')] // Added validation for the type field
    public string $type = '';

    #[Rule('nullable|image|max:2048')] // Handles a file upload, with size and type limits
    public $image = null; // Livewire handles this as a temporary file upload property

    // A property to hold the path to the current image when editing
    public ?string $currentImagePath = null;

    /**
     * Set the form properties for an existing amenity.
     * If no amenity is provided, it resets the form for creation.
     */
    public function setAmenity(?Amenity $amenity = null): void
    {
        $this->amenity = $amenity;

        // Populate form fields from the model
        $this->name = $amenity->name ?? '';
        $this->description = $amenity->description ?? '';
        $this->type = $amenity->type ?? '';

        // Store the existing image path for display and persistence
        $this->currentImagePath = $amenity->image ?? null;

        // Reset the image upload field to avoid validation issues
        $this->image = null;
    }

    /**
     * Handles the creation or update logic based on the presence of an amenity model.
     */
    public function save(): void
    {
        // Validate the form data against the rules defined above.
        $this->validate();

        $data = $this->all();

        // Handle image upload logic
        if ($this->image) {
            // Store the new image in the 'public/amenities' directory
            $imagePath = $this->image->store('amenities', 'public');
            $data['image'] = $imagePath;

            // If we are editing an amenity and a new image is uploaded, delete the old one
            if ($this->amenity && $this->amenity->image) {
                Storage::disk('public')->delete($this->amenity->image);
            }
        } else {
            // If no new image is uploaded, keep the existing one
            $data['image'] = $this->currentImagePath;
        }

        // Create or update the amenity
        if ($this->amenity) {
            $this->amenity->update($data);
        } else {
            Amenity::create($data);
        }

        // Reset the form fields after a successful save.
        $this->reset();
    }
}
