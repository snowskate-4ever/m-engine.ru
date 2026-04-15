<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Booking;
use Illuminate\Support\Carbon;

final class BookingAnalyticsService
{
    /**
     * @return list<array{month:string,count:int}>
     */
    public function bookingsPerMonth(int $monthsBack = 12): array
    {
        $since = Carbon::now()->subMonths(max(1, $monthsBack))->startOfMonth();

        $counts = [];
        Booking::query()
            ->whereNotNull('starts_at')
            ->where('starts_at', '>=', $since)
            ->orderBy('starts_at')
            ->cursor()
            ->each(function (Booking $booking) use (&$counts): void {
                $month = $booking->starts_at?->format('Y-m');
                if ($month === null) {
                    return;
                }
                $counts[$month] = ($counts[$month] ?? 0) + 1;
            });

        ksort($counts);
        $out = [];
        foreach ($counts as $month => $cnt) {
            $out[] = ['month' => $month, 'count' => $cnt];
        }

        return $out;
    }

    /**
     * @return array{total_bookings:int,with_search_request:int}
     */
    public function summary(): array
    {
        return [
            'total_bookings' => (int) Booking::query()->count(),
            'with_search_request' => (int) Booking::query()->whereNotNull('search_request_id')->count(),
        ];
    }
}
