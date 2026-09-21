<?php

use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Livewire\Card;
use Livewire\Livewire;
use Schmeits\PulsePanphp\Livewire\PanPhpAnalyticsCardComponent;

/*
 * This is where most of the value of this suite lives: the card renders through
 * the complete Pulse blade stack (x-pulse::card, -table, -select, -scroll). If
 * Pulse changes one of those components or the Card base class, this fails
 * immediately instead of only in a user's Pulse dashboard.
 *
 * The pan_analytics table normally comes from the panphp/pan migration. We
 * create it ourselves here in the in-memory sqlite database, behind a hard
 * check that we really are on :memory:.
 */

beforeEach(function () {
    $this->createPanAnalyticsTable();

    // The card is #[Lazy]: on the first render you get Pulse's skeleton back and
    // only after a second pass the real content. In tests we want to see the
    // real content right away, otherwise you are testing the skeleton. That the
    // card IS lazy is tested separately below.
    Livewire::withoutLazyLoading();
});

it('still extends the Pulse Card base class', function () {
    // The blade view uses $cols, $rows, $class and $expand. All four come from
    // Laravel\Pulse\Livewire\Card. If Pulse removes them, the card renders with
    // undefined variables.
    expect(new PanPhpAnalyticsCardComponent)->toBeInstanceOf(Card::class);
});

it('renders the card without errors when there are no analytics', function () {
    // The empty state is the scenario right after installation. It must work.
    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->assertSuccessful();
});

it('shows the name of an element in the card', function () {
    DB::table('pan_analytics')->insert([
        ['name' => 'checkout-button', 'impressions' => 100, 'hovers' => 50, 'clicks' => 25],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->assertSuccessful()
        ->assertSee('checkout-button');
});

it('shows impressions, hovers and clicks with their percentages', function () {
    // Proves that the global helpers from helpers.php are actually reachable
    // from the blade view. They are loaded via composer autoload.files, and that
    // is exactly the kind of coupling that silently disappears in a refactor.
    DB::table('pan_analytics')->insert([
        ['name' => 'button', 'impressions' => 200, 'hovers' => 50, 'clicks' => 10],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->assertSuccessful()
        ->assertSee('200')
        ->assertSee('50 (25%)')
        ->assertSee('10 (5%)');
});

it('sorts by impressions descending by default', function () {
    DB::table('pan_analytics')->insert([
        ['name' => 'rarely-seen', 'impressions' => 10, 'hovers' => 0, 'clicks' => 0],
        ['name' => 'often-seen', 'impressions' => 900, 'hovers' => 0, 'clicks' => 0],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->assertSuccessful()
        ->assertSeeInOrder(['often-seen', 'rarely-seen']);
});

it('sorts by clicks when orderBy is set to clicks', function () {
    // orderBy is a public #[Url] property: the user sets it via the select in
    // the card and via the query string. So the match() in render() really has
    // to pick that value up.
    DB::table('pan_analytics')->insert([
        ['name' => 'often-seen', 'impressions' => 900, 'hovers' => 0, 'clicks' => 1],
        ['name' => 'often-clicked', 'impressions' => 10, 'hovers' => 0, 'clicks' => 500],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->set('orderBy', 'clicks')
        ->assertSuccessful()
        ->assertSeeInOrder(['often-clicked', 'often-seen']);
});

it('sorts by hovers when orderBy is set to hovers', function () {
    DB::table('pan_analytics')->insert([
        ['name' => 'often-seen', 'impressions' => 900, 'hovers' => 1, 'clicks' => 0],
        ['name' => 'often-hovered', 'impressions' => 10, 'hovers' => 500, 'clicks' => 0],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->set('orderBy', 'hovers')
        ->assertSuccessful()
        ->assertSeeInOrder(['often-hovered', 'often-seen']);
});

it('falls back to impressions on an unknown sort value', function () {
    // orderBy comes from the query string and is therefore user input. The
    // default arm of the match() must prevent garbage from crashing the card.
    DB::table('pan_analytics')->insert([
        ['name' => 'rarely-seen', 'impressions' => 10, 'hovers' => 0, 'clicks' => 0],
        ['name' => 'often-seen', 'impressions' => 900, 'hovers' => 0, 'clicks' => 0],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->set('orderBy', 'garbage-from-the-query-string')
        ->assertSuccessful()
        ->assertSeeInOrder(['often-seen', 'rarely-seen']);
});

it('does not divide by zero when an element has zero impressions', function () {
    // Pan creates rows as soon as an element exists, even with 0 impressions.
    // Without the guard in toHumanReadablePercentage() the entire Pulse
    // dashboard would fall over with a DivisionByZeroError.
    DB::table('pan_analytics')->insert([
        ['name' => 'never-seen', 'impressions' => 0, 'hovers' => 0, 'clicks' => 0],
    ]);

    Livewire::test(PanPhpAnalyticsCardComponent::class)
        ->assertSuccessful()
        ->assertSee('0 (Infinity%)');
});
