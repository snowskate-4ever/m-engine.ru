<?php

namespace App\Console\Commands;

use App\Models\AuthAttempt;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupAuthAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:cleanup {--days=30 : Remove attempts older than X days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old authentication attempts';

    public function __construct(
        private readonly AuthService $authService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up auth attempts older than {$days} days...");

        // Отмечаем просроченные попытки как expired
        $expiredCount = $this->authService->cleanupExpiredAttempts();
        $this->info("Marked {$expiredCount} attempts as expired");

        // Удаляем старые завершенные попытки (success, failed, expired)
        $deleted = AuthAttempt::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['success', 'failed', 'expired'])
            ->delete();

        $this->info("Deleted {$deleted} old auth attempts");

        // Показываем статистику
        $this->showStatistics();

        return self::SUCCESS;
    }

    private function showStatistics(): void
    {
        $stats = [
            'total' => AuthAttempt::count(),
            'pending' => AuthAttempt::where('status', 'pending')->count(),
            'success' => AuthAttempt::where('status', 'success')->count(),
            'failed' => AuthAttempt::where('status', 'failed')->count(),
            'expired' => AuthAttempt::where('status', 'expired')->count(),
        ];

        $this->newLine();
        $this->info('Current statistics:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total', $stats['total']],
                ['Pending', $stats['pending']],
                ['Success', $stats['success']],
                ['Failed', $stats['failed']],
                ['Expired', $stats['expired']],
            ]
        );
    }
}
