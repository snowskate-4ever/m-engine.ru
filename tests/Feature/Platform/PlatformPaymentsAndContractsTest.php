<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Booking;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractTemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PlatformPaymentsAndContractsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_payment_for_booking_and_partial_refund_policy(): void
    {
        config(['platform_payments.refund.hours_before_full_refund' => 48]);
        config(['platform_payments.refund.partial_refund_ratio' => 0.5]);
        config(['platform_payments.escrow_enabled_for.booking' => false]);

        $payer = User::factory()->create();
        $booking = Booking::query()->create([
            'starts_at' => now()->addHours(10),
            'ends_at' => now()->addHours(12),
        ]);

        Sanctum::actingAs($payer);

        $r = $this->postJson("/api/platform/bookings/{$booking->id}/payments", [
            'amount_minor' => 10_000,
            'currency' => 'RUB',
        ]);
        $r->assertCreated();
        $paymentId = (int) $r->json('id');

        $this->postJson("/api/platform/payments/{$paymentId}/capture-stub")
            ->assertOk()
            ->assertJsonPath('status', 'captured');

        $ref = $this->postJson("/api/platform/payments/{$paymentId}/refund", ['reason' => 'cancel']);
        $ref->assertOk();
        $this->assertSame(5000, (int) $ref->json('refund_minor'));
        $this->assertSame('partial_refund_after_window', $ref->json('policy_label'));
    }

    public function test_contract_generate_and_dual_acceptance(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $tpl = ContractTemplate::query()->create([
            'slug' => 'studio_rental',
            'name' => 'Studio rental',
            'is_active' => true,
        ]);
        ContractTemplateVersion::query()->create([
            'contract_template_id' => $tpl->id,
            'version' => 1,
            'body_template' => 'Hello {{client_name}} between party A and B.',
            'variables_schema' => null,
        ]);

        Sanctum::actingAs($a);
        $gen = $this->postJson('/api/contracts/generate', [
            'template_slug' => 'studio_rental',
            'party_b_user_id' => $b->id,
            'variables' => ['client_name' => 'World'],
        ]);
        $gen->assertCreated();
        $contractId = (int) $gen->json('id');
        $this->assertStringContainsString('Hello World', (string) $gen->json('rendered_body'));

        $this->postJson("/api/contracts/{$contractId}/accept", ['side' => 'a'])->assertOk();

        Sanctum::actingAs($b);
        $this->postJson("/api/contracts/{$contractId}/accept", ['side' => 'b'])
            ->assertOk()
            ->assertJsonPath('status', Contract::STATUS_ACTIVE);
    }
}
