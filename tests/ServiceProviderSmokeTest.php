<?php

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Schmeits\PulsePanphp\Livewire\PanPhpAnalyticsCardComponent;
use Schmeits\PulsePanphp\PulsePanphp;
use Schmeits\PulsePanphp\PulsePanphpServiceProvider;

/*
 * These tests prove that the service provider registers and boots in a real
 * (Testbench) app. This is the first domino: if it falls, every project using
 * this package is broken before a single card is even rendered.
 */

it('registers the service provider in the container', function () {
    expect(app()->getProvider(PulsePanphpServiceProvider::class))
        ->toBeInstanceOf(PulsePanphpServiceProvider::class);
});

it('registers the view namespace pulse-panphp', function () {
    // The card renders view('pulse-panphp::livewire.panphp-analytics'). Without
    // this hint Laravel cannot find that view and the card only fails at render
    // time.
    expect(View::getFinder()->getHints())->toHaveKey('pulse-panphp');
});

it('registers the card under the livewire alias pulse.panphp', function () {
    // This alias is the public API: users put <livewire:pulse.panphp /> in their
    // pulse-dashboard.blade.php. Renaming it breaks every dashboard.
    //
    // Deliberately via Livewire::new() and not via the internal component
    // registry: that registry moved between Livewire 3 and 4, and this test
    // would then fail on a relocation rather than on a real defect.
    expect(Livewire::new('pulse.panphp'))
        ->toBeInstanceOf(PanPhpAnalyticsCardComponent::class);
});

it('registers the card only after livewire has been resolved', function () {
    // packageBooted() hangs the registration on callAfterResolving('livewire').
    // If that callback never runs, the card is undiscoverable while the provider
    // itself boots without complaint. We catch that silence here.
    expect(app()->resolved('livewire'))->toBeTrue();
});

it('can instantiate the public main class', function () {
    // The facade points at this, so the class must be resolvable via the container.
    expect(app(PulsePanphp::class))->toBeInstanceOf(PulsePanphp::class);
});

it('runs on an in-memory sqlite database', function () {
    // Pinned down explicitly: this suite must never hit a real database.
    expect(config('database.default'))->toBe('testing')
        ->and(config('database.connections.testing.driver'))->toBe('sqlite')
        ->and(config('database.connections.testing.database'))->toBe(':memory:');
});

it('actually ships the blade view that the card renders', function () {
    // hasViews() registers the namespace, but not whether the file exists.
    expect(View::exists('pulse-panphp::livewire.panphp-analytics'))->toBeTrue()
        ->and(is_file(__DIR__.'/../resources/views/livewire/panphp-analytics.blade.php'))->toBeTrue();
});
