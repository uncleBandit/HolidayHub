<?php

namespace App\Livewire\Forms;

use App\Models\Amenity;
use Illuminate\Support\Facades\Storage;
use Livewire\Form;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;

class AmenityForm extends Form
{
    use WithFileUploads;

    public ?Amenity $amenity = null;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:1000')]
    public string $description = '';

    #[Rule('required|string|in:hotel,room,package,villa')]
    public string $type = 'hotel';

    #[Rule('nullable|image|max:2048')]
    public $icon = null;

    public ?string $currentIconPath = null;

    #[Rule('boolean')]
    public bool $active = true;

    /**
     * Set the form properties for editing or reset for creation
     */
    public function setAmenity(?Amenity $amenity = null): void
    {
        $this->amenity = $amenity;

        if ($amenity) {
            $this->name = $amenity->name;
            $this->description = $amenity->description;
            $this->type = $amenity->type;
            $this->active = $amenity->active;
            $this->currentIconPath = $amenity->icon;
        } else {
            $this->resetFields();
        }

        $this->icon = null; // Reset the upload field
    }

    /**
     * Save or update the amenity
     */
    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'active' => $this->active,
        ];

        // Handle icon upload
        if ($this->icon) {
            $path = $this->icon->store('amenities/icons', 'public');
            $data['icon'] = $path;

            if ($this->amenity && $this->amenity->icon) {
                Storage::disk('public')->delete($this->amenity->icon);
            }
        } else {
            $data['icon'] = $this->currentIconPath;
        }

        if ($this->amenity) {
            $this->amenity->update($data);
        } else {
            $this->amenity = Amenity::create($data);
        }

        $this->resetFields();
    }

    /**
     * Delete the amenity and remove its icon
     */
    public function delete(): void
    {
        if ($this->amenity) {
            if ($this->amenity->icon) {
                Storage::disk('public')->delete($this->amenity->icon);
            }
            $this->amenity->delete();
            $this->resetFields();
        }
    }

    /**
     * Reset all form fields
     */
    private function resetFields(): void
    {
        $this->name = '';
        $this->description = '';
        $this->type = 'hotel';
        $this->icon = null;
        $this->currentIconPath = null;
        $this->active = true;
        $this->amenity = null;
    }
}
