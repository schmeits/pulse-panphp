<?php

use Illuminate\Support\Number;

/*
 * helpers.php is loaded via composer "autoload.files", not via PSR-4. That is a
 * separate, easily broken coupling: if someone removes the file from
 * composer.json, these functions disappear without any class being missing. The
 * card's blade view uses them directly.
 *
 * Note: these are UNPREFIXED global functions. They sit behind a
 * function_exists() guard, so an app that already defines them wins. We pin that
 * behavior down here so it stays a deliberate choice.
 */

it('loads the global helpers via composer autoload', function () {
    expect(function_exists('toHumanReadableNumber'))->toBeTrue()
        ->and(function_exists('toHumanReadablePercentage'))->toBeTrue();
});

it('formats a number readably', function () {
    expect(toHumanReadableNumber(1234))->toBe(Number::format(1234));
});

it('formats zero as zero and not as an empty string', function () {
    // 0 is a valid value in pan: an element that exists but has never been seen.
    // That must show up as "0" in the card, not as nothing.
    expect(toHumanReadableNumber(0))->toBe(Number::format(0));
});

it('converts a part of a total into a percentage', function () {
    expect(toHumanReadablePercentage(200, 50))->toBe(Number::percentage(25, 0, 1));
});

it('returns Infinity instead of dividing by zero', function () {
    // This is the only reason this helper exists. Without this guard the card
    // throws a DivisionByZeroError for every element with 0 impressions.
    expect(toHumanReadablePercentage(0, 0))->toBe('Infinity%');
});

it('also returns Infinity when there is a part but no total', function () {
    // Theoretically impossible in pan, but the guard must not assume that.
    expect(toHumanReadablePercentage(0, 5))->toBe('Infinity%');
});

it('rounds percentages to zero decimals', function () {
    // 1 out of 3 is 33.33%. The helper asks Number::percentage for 0 decimals,
    // so there must be no decimal separator in the result.
    expect(toHumanReadablePercentage(3, 1))->toBe(Number::percentage(100 / 3, 0, 1));
});
