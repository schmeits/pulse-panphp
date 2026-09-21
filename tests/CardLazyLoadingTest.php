<?php

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Livewire;
use Schmeits\PulsePanphp\Livewire\PanPhpAnalyticsCardComponent;

/*
 * Separate test file, because CardSmokeTest deliberately turns lazy loading OFF
 * to reach the real content. Livewire has no counterpart to switch it back on
 * afterwards, so this behavior belongs in its own file with a clean state.
 */

it('is marked as a lazy component', function () {
    // #[Lazy] is a deliberate choice: a Pulse dashboard with many cards must not
    // wait on this query. If the attribute disappears during a refactor, the
    // loading behavior of every dashboard changes without anything crashing.
    $attributes = (new ReflectionClass(PanPhpAnalyticsCardComponent::class))
        ->getAttributes(Lazy::class);

    expect($attributes)->not->toBeEmpty();
});

it('shows the skeleton first and no analytics yet', function () {
    // Proves that the lazy behavior actually works in a render: the first
    // response is Pulse's placeholder, not the table with data.
    $this->createPanAnalyticsTable();

    DB::table('pan_analytics')->insert([
        ['name' => 'checkout-button', 'impressions' => 100, 'hovers' => 0, 'clicks' => 0],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->assertSuccessful()
        ->assertDontSee('checkout-button');
});
