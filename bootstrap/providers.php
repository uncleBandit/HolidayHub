<?php

return [
    App\Providers\AppServiceProvider::class,
    // App\Providers\TelescopeServiceProvider::class,
    App\Providers\VoltServiceProvider::class,

    // The Filament administration panel.
    //
    // This must be listed here rather than left to the module registrar below.
    // Filament builds each panel's routes by iterating the registered panels at
    // route-registration time, and its service provider boots *before*
    // ModulesServiceProvider does. A panel registered during the boot phase —
    // which is when module providers are discovered — therefore arrives after
    // the routes have already been built, and the panel silently gets no routes
    // at all. Listing it here puts it in the register phase, ahead of every
    // boot.
    App\Modules\Administration\Providers\AdministrationPanelProvider::class,

    // Discovers and registers every module under app/Modules:
    // migrations, routes (web + api), module providers, views, factories,
    // translations, config and the polymorphic morph map.
    App\Providers\ModulesServiceProvider::class,
];
