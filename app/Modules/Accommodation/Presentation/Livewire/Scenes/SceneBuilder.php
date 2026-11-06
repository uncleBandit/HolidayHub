<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Scenes;

use App\Modules\Accommodation\Domain\Models\PropertyScene;
use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Where a host builds the reel walk through their property: upload films and
 * photos, tag each with the space it shows, write a title and caption, and
 * put them in the order a guest should walk through.
 *
 * Everything is saved the moment it changes, so the walk survives a refresh,
 * and every change re-checks that the signed-in host may edit the property.
 *
 * Embed with <livewire:scenes.scene-builder :owner="$villa" />.
 */
class SceneBuilder extends Component
{
    use WithFileUploads;

    /** The order a guest might naturally walk through each kind of stay. */
    public const JOURNEYS = [
        'villa' => ['arrival', 'living', 'kitchen', 'master', 'bathroom', 'pool', 'terrace', 'view', 'night'],
        'bed_and_breakfast' => ['arrival', 'courtyard', 'bedroom', 'bathroom', 'breakfast', 'lounge', 'neighbourhood'],
        'hotel' => ['arrival', 'entrance', 'pool', 'spa', 'bedroom', 'dining', 'view', 'night'],
        'activity' => ['meeting', 'journey', 'guide', 'first-sight', 'the-moment', 'golden-hour', 'wrap-up'],
        'package' => ['arrival', 'on-the-road', 'the-stay', 'experience', 'food', 'golden-hour', 'night', 'farewell'],
    ];

    public string $ownerType = '';

    public int $ownerId = 0;

    /** @var array<int, array<string, mixed>> */
    public array $scenes = [];

    /** New files: each becomes a scene. */
    public array $uploads = [];

    /** Replacement files keyed by scene id. */
    public array $replacements = [];

    /** Show the builder's own shooting tips when empty (off when the page has its own intro). */
    public bool $guide = true;

    public function mount(Model $owner, bool $guide = true): void
    {
        Gate::authorize('update', $owner);

        $this->guide = $guide;
        $this->ownerType = $owner->getMorphClass();
        $this->ownerId = $owner->getKey();
        $this->load();
    }

    public function updatedUploads(): void
    {
        $this->validate(
            ['uploads.*' => Media::UPLOAD_RULE],
            ['uploads.*.mimes' => 'Upload a film (MP4, WebM, MOV) or a photo (JPG, PNG, WebP).']
        );

        $owner = $this->owner();
        $journey = self::JOURNEYS[$this->ownerType] ?? self::JOURNEYS['villa'];
        $position = (int) $owner->scenes()->max('position');

        foreach ($this->uploads as $file) {
            $position++;
            $used = $owner->scenes()->pluck('space')->filter()->all();

            $owner->scenes()->create([
                'position' => $position,
                // Suggest the next space in a natural walk; the host can change it.
                'space' => collect($journey)->first(fn ($space) => ! in_array($space, $used, true)),
                'media_path' => $this->store($file),
                'media_type' => Media::isVideo($file->getClientOriginalName()) ? 'video' : 'photo',
            ]);
        }

        $this->uploads = [];
        $this->load();
    }

    public function updatedReplacements($file, $sceneId): void
    {
        $this->validate(["replacements.$sceneId" => Media::UPLOAD_RULE]);

        $scene = $this->scene((int) $sceneId);
        $old = $scene->media_path;

        $scene->update([
            'media_path' => $this->store($file),
            'media_type' => Media::isVideo($file->getClientOriginalName()) ? 'video' : 'photo',
        ]);

        $this->deleteFile($old);
        unset($this->replacements[$sceneId]);
        $this->load();
    }

    /**
     * Save a title, caption or space as soon as it changes.
     */
    public function updated(string $property, $value): void
    {
        if (! preg_match('/^scenes\.(\d+)\.(title|caption|space)$/', $property, $match)) {
            return;
        }

        $rules = [
            'title' => 'nullable|string|max:120',
            'caption' => 'nullable|string|max:500',
            'space' => 'nullable|in:'.implode(',', array_keys(PropertyScene::spacesFor($this->ownerType))),
        ];
        $this->validateOnly($property, [$property => $rules[$match[2]]]);

        $id = $this->scenes[(int) $match[1]]['id'] ?? null;
        if ($id) {
            $this->scene($id)->update([$match[2] => filled($value) ? trim((string) $value) : null]);
        }
    }

    public function move(int $sceneId, int $direction): void
    {
        $ids = collect($this->scenes)->pluck('id')->values();
        $from = $ids->search($sceneId);
        $to = $from + ($direction < 0 ? -1 : 1);

        if ($from === false || $to < 0 || $to >= $ids->count()) {
            return;
        }

        $order = $ids->all();
        [$order[$from], $order[$to]] = [$order[$to], $order[$from]];
        $this->saveOrder($order);
    }

    /** Called by drag-and-drop with the full new order of scene ids. */
    public function reorder(array $order): void
    {
        $known = collect($this->scenes)->pluck('id')->all();
        $order = array_values(array_filter(array_map('intval', $order), fn ($id) => in_array($id, $known, true)));

        if (count($order) === count($known)) {
            $this->saveOrder($order);
        }
    }

    public function remove(int $sceneId): void
    {
        $scene = $this->scene($sceneId);
        $this->deleteFile($scene->media_path);
        $scene->delete();
        $this->saveOrder(collect($this->scenes)->pluck('id')->reject(fn ($id) => $id === $sceneId)->values()->all());
    }

    public function render()
    {
        return view('livewire.scenes.scene-builder', [
            'spaces' => PropertyScene::spacesFor($this->ownerType),
            'films' => collect($this->scenes)->where('is_video', true)->count(),
        ]);
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function owner(): Model
    {
        $class = Relation::getMorphedModel($this->ownerType) ?? $this->ownerType;
        $owner = $class::findOrFail($this->ownerId);
        Gate::authorize('update', $owner);

        return $owner;
    }

    private function scene(int $id): PropertyScene
    {
        return $this->owner()->scenes()->whereKey($id)->firstOrFail();
    }

    private function saveOrder(array $ids): void
    {
        $owner = $this->owner();

        foreach (array_values($ids) as $i => $id) {
            $owner->scenes()->whereKey($id)->update(['position' => $i + 1]);
        }

        $this->load();
    }

    private function store($file): string
    {
        $folder = 'scenes/'.$this->ownerType.'-'.$this->ownerId;
        $path = $file->store($folder, 'public');

        if ($path === false) {
            throw new \RuntimeException('Unable to store the uploaded file.');
        }

        return $path;
    }

    private function deleteFile(?string $path): void
    {
        if (filled($path) && ! Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($path);
        }
    }

    private function load(): void
    {
        $this->scenes = $this->owner()->scenes()->get()->map(fn (PropertyScene $scene) => [
            'id' => $scene->id,
            'space' => $scene->space,
            'title' => (string) $scene->title,
            'caption' => (string) $scene->caption,
            'url' => $scene->url(),
            'is_video' => $scene->isVideo(),
        ])->values()->all();
    }
}
