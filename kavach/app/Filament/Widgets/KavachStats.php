<?php

namespace App\Filament\Widgets;

use App\Models\Activation;
use App\Models\License;
use App\Models\UpdateDownload;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KavachStats extends BaseWidget
{
    protected function getStats(): array
    {
        $expiringSoon = License::where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->count();

        return [
            Stat::make('Active licenses', License::where('status', 'active')->count())
                ->color('success'),
            Stat::make('Trials running', License::where('status', 'active')
                ->whereHas('plan', fn ($q) => $q->where('type', 'trial'))
                ->count())
                ->color('info'),
            Stat::make('Expiring in 7 days', $expiringSoon)
                ->color($expiringSoon > 0 ? 'warning' : 'gray')
                ->description('Renewal follow-up list'),
            Stat::make('Update downloads (30d)', UpdateDownload::where('created_at', '>=', now()->subDays(30))->count())
                ->color('primary'),
            Stat::make('Installations seen (7d)', Activation::where('last_check_at', '>=', now()->subDays(7))->count())
                ->description('Installations that phoned home this week'),
        ];
    }
}
