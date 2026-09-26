<?php

namespace App\Modules\Administration\Filament\Pages;

use App\Modules\Administration\Filament\Widgets\PlatformOverview;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The panel's landing page.
 *
 * Filament has no home page until one is registered, so without this /admin
 * 302s to the first navigation item instead of showing anything. Registering it
 * explicitly also gives PlatformOverview a page to live on.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Platform overview';

    /**
     * Widgets for the dashboard.
     *
     * @return array<int, class-string>
     */
    public function getWidgets(): array
    {
        return [
            PlatformOverview::class,
        ];
    }
}
