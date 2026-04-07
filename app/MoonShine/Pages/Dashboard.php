<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Enums\AiRequestSource;
use App\Models\AiUsageLedger;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

#[SkipMenu]
class Dashboard extends Page
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Dashboard';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        return [
            Grid::make([
                ValueMetric::make('Серверные токены (текущий месяц)')
                    ->value(fn () => $this->serverTokensThisMonth())
                    ->columnSpan(6),
                ValueMetric::make('Оценка расхода ledger за месяц (руб.)')
                    ->value(fn () => $this->ledgerInternalCostThisMonthRub())
                    ->columnSpan(6),
            ]),
        ];
    }

    private function serverTokensThisMonth(): int
    {
        $tz = (string) config('billing.quota_timezone', 'Europe/Moscow');
        $start = now()->timezone($tz)->startOfMonth()->utc();
        $end = now()->timezone($tz)->endOfMonth()->utc();

        $v = AiUsageLedger::query()
            ->where('source', AiRequestSource::Server->value)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(tokens_prompt + tokens_completion), 0) as agg')
            ->value('agg');

        return (int) $v;
    }

    private function ledgerInternalCostThisMonthRub(): string
    {
        $tz = (string) config('billing.quota_timezone', 'Europe/Moscow');
        $start = now()->timezone($tz)->startOfMonth()->utc();
        $end = now()->timezone($tz)->endOfMonth()->utc();

        $sum = AiUsageLedger::query()
            ->where('source', AiRequestSource::Server->value)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('estimated_internal_cost')
            ->sum('estimated_internal_cost');

        return number_format((float) $sum, 2, '.', '');
    }
}
