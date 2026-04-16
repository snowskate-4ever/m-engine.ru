# Matching / Integration Runbook

## SLO Targets
- Matching scheduler success rate: >= 99.5%
- Integration webhook processing success: >= 99.9%
- Mobile sync manifest p95 latency: <= 400ms

## Operational Commands
- `php artisan music:run-matching --scope=all`
- `php artisan metrics:baseline-snapshot --days=30`
- `php artisan queue:work --tries=3 --max-jobs=1000`

## Incident Checklist
1. Check recent failed jobs and matching logs.
2. Verify rate limiter pressure for integration tokens.
3. Validate webhook signature secret and idempotency keys.
4. Replay safe tasks from dead-letter queue.

## Recovery
- Temporarily switch matching to rule-based mode if AI provider is degraded.
- Keep `explanation_level=summary` during degraded mode for postmortem analysis.
