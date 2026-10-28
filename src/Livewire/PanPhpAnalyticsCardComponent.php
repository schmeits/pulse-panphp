<?php

namespace Schmeits\PulsePanphp\Livewire;

use Illuminate\Contracts\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Schmeits\PulsePanphp\Models\PanAnalytics;

#[Lazy]
class PanPhpAnalyticsCardComponent extends Card
{
    #[Url(as: 'pan-php')]
    public string $orderBy = 'impressions';

    public function render(): View
    {
        $analytics = PanAnalytics::query();

        match ($this->orderBy) {
            'clicks' => $analytics->orderByDesc('clicks'),
            'hovers' => $analytics->orderByDesc('hovers'),
            default => $analytics->orderByDesc('impressions')
        };

        /** @var view-string $view */
        $view = 'pulse-panphp::livewire.panphp-analytics';

        return view($view, [
            'analytics' => $analytics->get(),
        ]);
    }
}
