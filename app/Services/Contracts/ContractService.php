<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractAcceptanceAudit;
use App\Models\ContractTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ContractService
{
    /**
     * @param  array<string, string|int|float|null>  $variables
     */
    public function generateFromVersion(
        ContractTemplateVersion $version,
        Model $partyA,
        Model $partyB,
        array $variables = [],
    ): Contract {
        $body = $version->body_template;
        foreach ($variables as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return Contract::query()->create([
            'contract_template_version_id' => $version->id,
            'party_a_type' => $partyA->getMorphClass(),
            'party_a_id' => $partyA->getKey(),
            'party_b_type' => $partyB->getMorphClass(),
            'party_b_id' => $partyB->getKey(),
            'rendered_body' => $body,
            'filled_variables' => $variables,
            'status' => Contract::STATUS_AWAITING,
        ]);
    }

    public function acceptPartyA(Contract $contract, User $user): Contract
    {
        return $this->acceptSide($contract, $user, 'a');
    }

    public function acceptPartyB(Contract $contract, User $user): Contract
    {
        return $this->acceptSide($contract, $user, 'b');
    }

    private function acceptSide(Contract $contract, User $user, string $side): Contract
    {
        return DB::transaction(function () use ($contract, $user, $side): Contract {
            $contract->refresh();

            if ($side === 'a') {
                if ($contract->party_a_accepted_at !== null) {
                    throw new \InvalidArgumentException('Party A already accepted.');
                }
                $contract->forceFill([
                    'party_a_accepted_at' => now(),
                    'party_a_accepted_by_user_id' => $user->id,
                ])->save();
            } else {
                if ($contract->party_b_accepted_at !== null) {
                    throw new \InvalidArgumentException('Party B already accepted.');
                }
                $contract->forceFill([
                    'party_b_accepted_at' => now(),
                    'party_b_accepted_by_user_id' => $user->id,
                ])->save();
            }

            ContractAcceptanceAudit::query()->create([
                'contract_id' => $contract->id,
                'user_id' => $user->id,
                'side' => $side,
                'action' => 'accept',
                'payload' => ['ip' => request()?->ip()],
                'created_at' => now(),
            ]);

            $contract->refresh();
            if ($contract->party_a_accepted_at && $contract->party_b_accepted_at) {
                $contract->forceFill(['status' => Contract::STATUS_ACTIVE])->save();
            }

            return $contract->fresh();
        });
    }
}
