@props(['item'])

@php
    // The /discover "collections" mix Villas and Experiences in one list, so
    // this card has to render either. It reuses the two dedicated components
    // instead of duplicating their markup.
    $isVilla = $item instanceof \App\Modules\Accommodation\Domain\Models\Villa;
    $isExperience = $item instanceof \App\Modules\Activities\Domain\Models\Experience;

    if ($isVilla) {
        echo view('components.villa-card', ['villa' => $item])->render();
        return;
    }

    if ($isExperience) {
        echo view('components.experience-card', ['experience' => $item])->render();
        return;
    }
@endphp

{{-- Unknown polymorphic type: render nothing rather than a broken card. --}}
<div class="hidden"></div>
