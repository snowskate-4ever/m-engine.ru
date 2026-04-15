<?php

declare(strict_types=1);

namespace App\Services\PlatformPayments;

/**
 * Настраиваемые депозиты/штрафы (заготовка для владельцев ресурсов).
 *
 * @param  array<string, mixed>  $resourceRules
 * @return array{deposit_minor: int, penalty_minor: int, notes: string}
 */
final class DepositPenaltyEvaluator
{
    public function evaluate(int $baseAmountMinor, array $resourceRules = []): array
    {
        if ($resourceRules === []) {
            return ['deposit_minor' => 0, 'penalty_minor' => 0, 'notes' => 'no_rules'];
        }

        $deposit = max(0, (int) ($resourceRules['deposit_minor'] ?? 0));
        $penalty = max(0, (int) ($resourceRules['penalty_minor'] ?? 0));

        return [
            'deposit_minor' => min($baseAmountMinor, $deposit),
            'penalty_minor' => min($baseAmountMinor, $penalty),
            'notes' => (string) ($resourceRules['notes'] ?? 'custom'),
        ];
    }
}
