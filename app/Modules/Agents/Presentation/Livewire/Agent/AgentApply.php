<?php

namespace App\Modules\Agents\Presentation\Livewire\Agent;

use App\Modules\Agents\Application\Services\AgentOnboarding;
use App\Support\Countries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * "Sell journeys on HolidayHub": a signed-in traveller applies as a travel
 * agent. They can start building packages at once; our team verifies the
 * agency before any package can be submitted for review.
 */
#[Layout('layouts.discovery')]
class AgentApply extends Component
{
    public const BUSINESS_TYPES = [
        'agency' => ['Travel agency', 'A registered agency or tour operator'],
        'individual' => ['Independent agent', 'You plan trips on your own'],
        'corporate' => ['Corporate travel', 'Trips for a company or its clients'],
    ];

    public string $agency_name = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $agency_license = '';

    public string $business_type = 'agency';

    public string $specialization = '';

    public string $country_code = '';

    public string $city = '';

    public string $bio = '';

    public bool $agree = false;

    public function mount()
    {
        if (Auth::user()->hasRole('agent') && Auth::user()->agent) {
            return $this->redirectRoute('agent.dashboard', navigate: false);
        }

        $parts = preg_split('/\s+/', trim((string) Auth::user()->name), 2) ?: [];
        $this->first_name = $parts[0] ?? '';
        $this->last_name = $parts[1] ?? '';
    }

    public function apply()
    {
        $data = $this->validate([
            'agency_name' => 'required|string|min:2|max:255',
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'phone' => ['nullable', 'string', 'max:40', Rule::unique('agents', 'phone')->ignore(Auth::user()->agent?->id)],
            'agency_license' => 'nullable|string|max:120',
            'business_type' => ['required', Rule::in(array_keys(self::BUSINESS_TYPES))],
            'specialization' => 'nullable|string|max:120',
            'country_code' => ['required', Rule::in(Countries::codes())],
            'city' => 'nullable|string|max:120',
            'bio' => 'nullable|string|max:250',
            'agree' => 'accepted',
        ], [
            'agency_name.required' => 'What’s your agency or trading name?',
            'country_code.required' => 'Where is your agency based?',
            'agree.accepted' => 'Please confirm you only sell journeys you can stand behind.',
            'phone.unique' => 'That phone number is already used by another agent.',
        ]);

        app(AgentOnboarding::class)->apply(Auth::user(), [
            ...$data,
            'country' => Countries::name($data['country_code']),
        ]);

        session()->flash('success', 'Welcome aboard. You can start building journeys now; we’ll verify your agency before your first one goes live.');

        return $this->redirectRoute('agent.dashboard', navigate: false);
    }

    public function render()
    {
        return view('livewire.agent.agent-apply', [
            'countries' => Countries::all(),
            'businessTypes' => self::BUSINESS_TYPES,
        ])->title('Become a travel agent · HolidayHub');
    }
}
