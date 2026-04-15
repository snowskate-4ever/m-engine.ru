<?php

declare(strict_types=1);

namespace App\Http\Controllers\api\Integration;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsCsvExporter;
use App\Services\Analytics\BookingAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class IntegrationAnalyticsController extends Controller
{
    public function bookingsSummary(BookingAnalyticsService $analytics): JsonResponse
    {
        return response()->json($analytics->summary());
    }

    public function bookingsByMonth(Request $request, BookingAnalyticsService $analytics): JsonResponse
    {
        $months = (int) $request->query('months', '12');

        return response()->json([
            'series' => $analytics->bookingsPerMonth(max(1, min(36, $months))),
        ]);
    }

    public function exportBookingsCsv(BookingAnalyticsService $analytics, AnalyticsCsvExporter $exporter): StreamedResponse
    {
        $series = $analytics->bookingsPerMonth(24);
        $rows = [];
        foreach ($series as $row) {
            $rows[] = ['month' => $row['month'], 'count' => $row['count']];
        }

        return $exporter->streamDownload('bookings-by-month.csv', ['month', 'count'], $rows);
    }
}
