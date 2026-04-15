<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MatchingRunLog;
use App\Models\User;
use App\Services\Music\MatchingControlSettingsService;
use App\Services\Music\SearchMatchingService;
use Illuminate\Console\Command;

class RunSearchMatchingCommand extends Command
{
    protected $signature = 'music:run-matching {--scope= : all|profiles|entities} {--manual : mark run as manual} {--dry-run : compute only, do not write matches} {--max-requests= : limit number of search requests per run} {--run-by-user-id= : audit user id for manual run} {--explanation-level=off : off|summary|full}';

    protected $description = 'Run ads/search-request matching pipeline and write run logs';

    public function __construct(
        private readonly SearchMatchingService $matchingService,
        private readonly MatchingControlSettingsService $controlSettingsService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isAutomatic = ! (bool) $this->option('manual');
        $settings = $this->controlSettingsService->get();

        if ($isAutomatic && ! $this->controlSettingsService->isAutomaticRunAllowed()) {
            $this->info('Automatic matching skipped by control settings.');

            return self::SUCCESS;
        }

        $scope = trim((string) $this->option('scope'));
        if ($scope === '') {
            $scope = (string) $settings->default_scope;
        }
        $dryRun = (bool) $this->option('dry-run');
        $maxRequestsRaw = trim((string) $this->option('max-requests'));
        $maxRequests = $maxRequestsRaw !== '' ? max(1, (int) $maxRequestsRaw) : null;
        $explanationLevel = strtolower(trim((string) $this->option('explanation-level')));
        if (! in_array($explanationLevel, ['off', 'summary', 'full'], true)) {
            $explanationLevel = 'off';
        }
        $runByUserIdRaw = trim((string) $this->option('run-by-user-id'));
        $runBy = $runByUserIdRaw !== ''
            ? User::query()->find((int) $runByUserIdRaw)
            : null;

        config()->set('ai.matching.enabled', $isAutomatic ? (bool) $settings->is_enabled : true);
        config()->set('ai.matching.provider', (string) $settings->provider);
        config()->set('ai.matching.model', (string) $settings->model);
        config()->set('ai.matching.score_threshold', (float) $settings->score_threshold);
        config()->set('ai.matching.weights', is_array($settings->weights) ? $settings->weights : []);

        /** @var MatchingRunLog $log */
        $log = $this->matchingService->run(
            scope: $scope,
            runBy: $runBy,
            isAutomatic: $isAutomatic,
            dryRun: $dryRun,
            maxRequests: $maxRequests,
            explanationLevel: $explanationLevel,
            runParams: [
                'scope' => $scope,
                'manual' => ! $isAutomatic,
                'dry_run' => $dryRun,
                'max_requests' => $maxRequests,
                'run_by_user_id' => $runBy?->id,
                'explanation_level' => $explanationLevel,
            ],
        );
        $this->info(sprintf(
            'Matching run logged: processed=%d matched=%d failed=%d dry_run=%s explanation=%s',
            (int) $log->processed_count,
            (int) $log->matched_count,
            (int) $log->failed_count,
            $dryRun ? 'yes' : 'no',
            $explanationLevel,
        ));

        return self::SUCCESS;
    }
}
