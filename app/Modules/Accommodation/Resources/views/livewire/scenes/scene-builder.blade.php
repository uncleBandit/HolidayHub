<div class="space-y-5">
    {{-- Add scenes --}}
    <label class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-[1.2rem] border-2 border-dashed border-line bg-[#0C1511] px-6 py-10 text-center text-white transition-colors hover:border-clay">
        <span class="grid h-14 w-14 place-items-center rounded-full border border-white/25 bg-white/10 transition-transform group-hover:scale-105"><x-line-icon name="play" :size="20" filled /></span>
        <span class="font-dm-serif text-[1.35rem]">Add scenes to your walk-through</span>
        <span class="max-w-md text-[12px] leading-relaxed text-white/65">Upload films (MP4, WebM, MOV) or photos, one space each. Several at once is fine; they join the walk in the order you pick them. Up to 200 MB per file.</span>
        <input type="file" multiple accept="video/mp4,video/webm,video/quicktime,.mov,.m4v,image/*" wire:model="uploads" class="sr-only">
    </label>
    <div wire:loading wire:target="uploads" class="text-[12px] font-bold text-clay">Uploading… films can take a minute.</div>
    @error('uploads.*') <span class="auth-error">{{ $message }}</span> @enderror

    @if (count($scenes))
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-[12px] text-muted"><b class="text-ink">{{ count($scenes) }} {{ Str::plural('scene', count($scenes)) }}</b> · {{ $films }} {{ Str::plural('film', $films) }} · drag to reorder. Every change saves straight away.</p>
            <button type="button" x-on:click="$dispatch('open-reel-walk')" class="dv-btn dv-btn-ghost !min-h-[2.5rem]"><x-line-icon name="play" :size="13" filled /> Preview the walk</button>
        </div>

        <ol
            class="space-y-3"
            x-data="{
                dragging: null,
                drop(target) {
                    if (this.dragging === null || this.dragging === target) return;
                    const items = [...this.$el.querySelectorAll('[data-scene]')].map((el) => +el.dataset.scene);
                    const from = items.indexOf(this.dragging);
                    items.splice(from, 1);
                    items.splice(items.indexOf(target) + (from <= items.indexOf(target) ? 1 : 0), 0, this.dragging);
                    this.dragging = null;
                    $wire.reorder(items);
                },
            }"
        >
            @foreach ($scenes as $i => $scene)
                <li
                    data-scene="{{ $scene['id'] }}"
                    wire:key="scene-{{ $scene['id'] }}"
                    draggable="true"
                    x-on:dragstart="dragging = {{ $scene['id'] }}"
                    x-on:dragover.prevent
                    x-on:drop.prevent="drop({{ $scene['id'] }})"
                    class="grid gap-4 rounded-[1.1rem] border border-line bg-white p-3 transition-shadow hover:shadow-[0_14px_40px_-20px_rgba(20,36,28,0.45)] sm:grid-cols-[auto_9.5rem_1fr]"
                    :class="dragging === {{ $scene['id'] }} && 'opacity-50'"
                >
                    <div class="flex items-center gap-2 sm:flex-col sm:justify-center">
                        <span class="cursor-grab select-none text-[18px] leading-none text-muted" title="Drag to reorder" aria-hidden="true">⠿</span>
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-forest font-dm-serif text-[0.95rem] text-white">{{ $i + 1 }}</span>
                        <div class="flex gap-1 sm:flex-col">
                            <button type="button" wire:click="move({{ $scene['id'] }}, -1)" @disabled($i === 0) class="grid h-7 w-7 place-items-center rounded-full border border-line text-muted hover:border-forest hover:text-forest disabled:opacity-30" aria-label="Move earlier"><span class="-rotate-90"><x-line-icon name="chevron" :size="12" /></span></button>
                            <button type="button" wire:click="move({{ $scene['id'] }}, 1)" @disabled($loop->last) class="grid h-7 w-7 place-items-center rounded-full border border-line text-muted hover:border-forest hover:text-forest disabled:opacity-30" aria-label="Move later"><span class="rotate-90"><x-line-icon name="chevron" :size="12" /></span></button>
                        </div>
                    </div>

                    <div class="relative aspect-[9/12] overflow-hidden rounded-[0.8rem] bg-[#0C1511] sm:aspect-[4/5]">
                        @if ($scene['url'] && $scene['is_video'])
                            <video src="{{ $scene['url'] }}" class="h-full w-full object-cover" muted loop playsinline preload="metadata" x-on:mouseenter="$el.play()" x-on:mouseleave="$el.pause()"></video>
                            <span class="pointer-events-none absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-black/55 px-2 py-0.5 text-[9px] font-extrabold uppercase text-white"><x-line-icon name="play" :size="9" filled /> Film</span>
                        @elseif ($scene['url'])
                            <img src="{{ $scene['url'] }}" alt="" class="h-full w-full object-cover">
                        @endif
                        <label class="absolute inset-x-2 bottom-2 cursor-pointer rounded-full bg-white/90 py-1 text-center text-[10.5px] font-extrabold text-forest backdrop-blur hover:bg-white">
                            Replace
                            <input type="file" accept="video/mp4,video/webm,video/quicktime,.mov,.m4v,image/*" wire:model="replacements.{{ $scene['id'] }}" class="sr-only">
                        </label>
                        <div wire:loading wire:target="replacements.{{ $scene['id'] }}" class="absolute inset-0 grid place-items-center bg-black/55 text-[11px] font-bold text-white">Uploading…</div>
                    </div>

                    <div class="grid content-start gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="auth-label">Space</span>
                            <select wire:model.live="scenes.{{ $i }}.space" class="auth-input">
                                <option value="">Choose…</option>
                                @foreach ($spaces as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="auth-label">Title</span>
                            <input type="text" maxlength="120" wire:model.blur="scenes.{{ $i }}.title" placeholder="{{ $i === 0 ? 'Through the frangipani gate' : 'Morning light in the master suite' }}" class="auth-input">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="auth-label">Caption <span class="font-medium normal-case tracking-normal text-muted">(what you want guests to notice)</span></span>
                            <textarea rows="2" maxlength="500" wire:model.blur="scenes.{{ $i }}.caption" placeholder="The pool is heated, and catches the last of the sun until seven." class="auth-input !h-auto py-2.5"></textarea>
                        </label>
                        @foreach (['space', 'title', 'caption'] as $field)
                            @error("scenes.$i.$field") <span class="auth-error sm:col-span-2">{{ $message }}</span> @enderror
                        @endforeach
                        @error("replacements.{$scene['id']}") <span class="auth-error sm:col-span-2">{{ $message }}</span> @enderror
                        <div class="sm:col-span-2">
                            <button type="button" wire:click="remove({{ $scene['id'] }})" wire:confirm="Remove this scene from the walk-through?" class="text-[11.5px] font-extrabold text-muted hover:text-clay">Remove scene</button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        {{-- Exactly what guests will see --}}
        <div wire:key="preview-{{ md5(json_encode($scenes)) }}">
            <x-stay-detail.reel-walk :scenes="$scenes" name="Your walk-through" :inline="false" />
        </div>
    @elseif ($guide)
        <div class="rounded-[1.1rem] border border-line bg-cream p-5">
            <p class="font-dm-serif text-[1.3rem] text-forest">Walk guests through, the way you would.</p>
            <p class="mt-1.5 max-w-[40rem] text-[12.5px] leading-relaxed text-muted">Short films work best: 5–20 seconds each, one space at a time, shot as you’d walk it. A journey guests love:</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach (\App\Modules\Accommodation\Presentation\Livewire\Scenes\SceneBuilder::JOURNEYS[$ownerType] ?? [] as $n => $space)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3 py-1 text-[11.5px] font-bold text-forest"><span class="text-clay">{{ $n + 1 }}</span> {{ $spaces[$space] }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>
