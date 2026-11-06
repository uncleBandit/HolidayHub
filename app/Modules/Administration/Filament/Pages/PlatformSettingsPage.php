<?php

namespace App\Modules\Administration\Filament\Pages;

use App\Modules\Accommodation\Presentation\Livewire\Hotel\HotelCreate;
use App\Modules\Administration\Application\Services\PlatformSettings;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Booking\Application\Services\CancellationTerms;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Settings that change how the platform behaves, without a deploy.
 *
 * Each field maps to a key in PlatformSettings, and each one is read by the
 * app (support contact in guest-facing messages, the site banner, the default
 * cancellation policy and blackout dates in cancellations, new agents'
 * commission), so nothing here is decorative. Every save is audited.
 */
class PlatformSettingsPage extends Page
{
    protected static ?string $slug = 'platform-settings';

    protected static ?string $title = 'Platform settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.view') === true;
    }

    private static function canManage(): bool
    {
        return auth()->user()?->can('settings.manage') === true;
    }

    public function mount(): void
    {
        $settings = app(PlatformSettings::class);

        $this->form->fill([
            'support_email' => $settings->get('support.email'),
            'support_phone' => $settings->get('support.phone'),
            'banner_enabled' => (bool) $settings->get('banner.enabled'),
            'banner_message' => $settings->get('banner.message'),
            'banner_tone' => $settings->get('banner.tone') ?: 'info',
            'default_cancellation' => $settings->get('bookings.default_cancellation'),
            'blackouts' => array_values((array) $settings->get('bookings.blackouts', [])),
            'agent_commission' => $settings->get('agents.default_commission'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $presets = collect(HotelCreate::CANCELLATION)
            ->mapWithKeys(fn (string $text, string $name) => [$text => str($name)->headline().': '.$text])
            ->prepend('Standard: '.CancellationTerms::DEFAULT_TEXT, CancellationTerms::DEFAULT_TEXT)
            ->all();

        return $schema
            ->disabled(! self::canManage())
            ->statePath('data')
            ->components([
                Section::make('Support contact')
                    ->description('Shown to guests and hosts wherever they are told to contact support, e.g. when an account is suspended or a booking can no longer be cancelled online.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('support_email')->label('Support email')->email()->required()->maxLength(190),
                        TextInput::make('support_phone')->label('Support phone')->tel()->maxLength(40)->placeholder('Optional'),
                    ]),
                Section::make('Site banner')
                    ->description('A notice across the top of every guest page, for maintenance windows, holidays or service news.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('banner_enabled')->label('Show the banner')->live()->columnSpanFull(),
                        Textarea::make('banner_message')->label('Message')->rows(2)->maxLength(240)
                            ->required(fn (Get $get) => (bool) $get('banner_enabled'))
                            ->columnSpanFull(),
                        Select::make('banner_tone')->label('Tone')
                            ->options(['info' => 'Information (green)', 'warning' => 'Warning (clay)'])
                            ->selectablePlaceholder(false),
                    ]),
                Section::make('Bookings')
                    ->schema([
                        Select::make('default_cancellation')
                            ->label('Default cancellation policy')
                            ->helperText('Applies to stays and experiences whose host has not chosen a policy. Refunds are worked out from this wording.')
                            ->options($presets)
                            ->required()
                            ->selectablePlaceholder(false),
                        Repeater::make('blackouts')
                            ->label('Cancellation blackout periods')
                            ->helperText('Guests can’t cancel online bookings that start in these periods (they can still contact support).')
                            ->schema([
                                TextInput::make('label')->placeholder('e.g. Festive season')->maxLength(80),
                                DatePicker::make('start')->required()->native(false),
                                DatePicker::make('end')->required()->native(false)->afterOrEqual('start'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add a blackout period')
                            ->defaultItems(0)
                            ->reorderable(false),
                    ]),
                Section::make('Travel agents')
                    ->schema([
                        TextInput::make('agent_commission')
                            ->label('Default commission for new agents')
                            ->numeric()->minValue(0)->maxValue(100)->step(0.5)->suffix('%')->required()
                            ->helperText('New agents start on this rate; change an individual agent’s rate on their page under Agents.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save')->visible(self::canManage()),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(self::canManage(), 403);

        $data = $this->form->getState();

        $changes = app(PlatformSettings::class)->save([
            'support.email' => trim($data['support_email']),
            'support.phone' => filled($data['support_phone'] ?? null) ? trim($data['support_phone']) : null,
            'banner.enabled' => (bool) ($data['banner_enabled'] ?? false),
            'banner.message' => filled($data['banner_message'] ?? null) ? trim($data['banner_message']) : null,
            'banner.tone' => $data['banner_tone'] ?? 'info',
            'bookings.default_cancellation' => $data['default_cancellation'],
            'bookings.blackouts' => collect($data['blackouts'] ?? [])
                ->map(fn (array $row) => [
                    'label' => filled($row['label'] ?? null) ? trim($row['label']) : null,
                    'start' => (string) $row['start'],
                    'end' => (string) $row['end'],
                ])
                ->sortBy('start')
                ->values()
                ->all(),
            'agents.default_commission' => (float) $data['agent_commission'],
        ], auth()->id());

        if ($changes === []) {
            Notification::make()->title('Nothing changed.')->send();

            return;
        }

        app(AuditRecorder::class)->record(
            'settings.updated',
            null,
            auth()->user(),
            before: array_map(fn ($change) => $change[0], $changes),
            after: array_map(fn ($change) => $change[1], $changes),
        );

        Notification::make()->success()->title('Settings saved.')->body(count($changes).' '.str('setting')->plural(count($changes)).' updated.')->send();
    }
}
