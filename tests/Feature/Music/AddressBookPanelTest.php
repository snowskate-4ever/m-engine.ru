<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Livewire\Music\AddressBookPanel;
use App\Models\Country;
use App\Models\Musician;
use App\Models\RecordLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AddressBookPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_address_for_musician(): void
    {
        $country = Country::query()->create([
            'name' => 'Testland',
            'code' => 'TL',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $musician = Musician::query()->create([
            'name' => 'Addr Musician '.uniqid('', true),
            'description' => 'Bio',
            'user_id' => $user->id,
            'slug' => 'addr-m-'.uniqid('', true),
            'public_page_enabled' => false,
        ]);

        Livewire::actingAs($user)
            ->test(AddressBookPanel::class, ['ownerKind' => 'musician', 'ownerId' => $musician->id])
            ->call('openCreate')
            ->set('form_country_id', $country->id)
            ->set('form_street', 'Main street')
            ->set('form_house', '1')
            ->set('form_address_type', 'home')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('addresses', [
            'addressable_id' => $musician->id,
            'addressable_type' => $musician->getMorphClass(),
            'country_id' => $country->id,
            'street' => 'Main street',
            'house' => '1',
        ]);
    }

    public function test_owner_can_create_address_for_record_label(): void
    {
        $country = Country::query()->create([
            'name' => 'Labeland',
            'code' => 'LB',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $label = RecordLabel::query()->create([
            'name' => 'Addr Label '.uniqid('', true),
            'description' => null,
            'owner_user_id' => $user->id,
            'slug' => null,
            'public_page_enabled' => false,
        ]);

        Livewire::actingAs($user)
            ->test(AddressBookPanel::class, ['ownerKind' => 'record_label', 'ownerId' => $label->id])
            ->call('openCreate')
            ->set('form_country_id', $country->id)
            ->set('form_street', 'Label street')
            ->set('form_house', '2')
            ->set('form_address_type', 'office')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('addresses', [
            'addressable_id' => $label->id,
            'addressable_type' => $label->getMorphClass(),
            'country_id' => $country->id,
            'street' => 'Label street',
            'house' => '2',
        ]);
    }
}
