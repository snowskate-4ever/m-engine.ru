<?php

declare(strict_types=1);

namespace App\Livewire\Music;

use App\Models\Address;
use App\Models\City;
use App\Models\Country;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Region;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddressBookPanel extends Component
{
    public string $ownerKind = 'musician';

    public int $ownerId = 0;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?string $notice = null;

    public ?int $form_country_id = null;

    public ?int $form_region_id = null;

    public ?int $form_city_id = null;

    public string $form_street = '';

    public string $form_house = '';

    public string $form_building = '';

    public string $form_apartment = '';

    public string $form_postal_code = '';

    public string $form_additional_info = '';

    public string $form_name = '';

    public string $form_address_type = 'home';

    public bool $form_is_primary = false;

    public bool $form_is_public = true;

    public function mount(string $ownerKind, int $ownerId): void
    {
        if (! in_array($ownerKind, $this->allowedKinds(), true)) {
            abort(404);
        }
        $this->ownerKind = $ownerKind;
        $this->ownerId = $ownerId;
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);
    }

    public function openCreate(): void
    {
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);
        $this->editingId = null;
        $this->resetFormDefaults($owner);
        $this->showForm = true;
        $this->notice = null;
    }

    public function openEdit(int $addressId): void
    {
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);
        $address = $this->findOwnedAddress($addressId);
        $this->editingId = $address->id;
        $this->form_country_id = $address->country_id;
        $this->form_region_id = $address->region_id;
        $this->form_city_id = $address->city_id;
        $this->form_street = (string) ($address->street ?? '');
        $this->form_house = (string) ($address->house ?? '');
        $this->form_building = (string) ($address->building ?? '');
        $this->form_apartment = (string) ($address->apartment ?? '');
        $this->form_postal_code = (string) ($address->postal_code ?? '');
        $this->form_additional_info = (string) ($address->additional_info ?? '');
        $this->form_name = (string) ($address->name ?? '');
        $this->form_address_type = (string) $address->address_type;
        $this->form_is_primary = (bool) $address->is_primary;
        $this->form_is_public = (bool) $address->is_public;
        $this->showForm = true;
        $this->notice = null;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);

        $this->form_region_id = $this->form_region_id ?: null;
        $this->form_city_id = $this->form_city_id ?: null;

        $countryId = $this->form_country_id;
        $rules = [
            'form_country_id' => ['required', 'integer', 'exists:countries,id'],
            'form_region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('country_id', (int) $countryId)],
            'form_city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('country_id', (int) $countryId)],
            'form_street' => ['nullable', 'string', 'max:255'],
            'form_house' => ['nullable', 'string', 'max:255'],
            'form_building' => ['nullable', 'string', 'max:50'],
            'form_apartment' => ['nullable', 'string', 'max:50'],
            'form_postal_code' => ['nullable', 'string', 'max:20'],
            'form_additional_info' => ['nullable', 'string', 'max:2000'],
            'form_name' => ['nullable', 'string', 'max:100'],
            'form_address_type' => ['required', 'string', Rule::in(array_keys(Address::TYPES))],
            'form_is_primary' => ['boolean'],
            'form_is_public' => ['boolean'],
        ];

        $this->validate($rules, [], [
            'form_country_id' => __('ui.address.country'),
            'form_region_id' => __('ui.address.region'),
            'form_city_id' => __('ui.address.city'),
        ]);

        $payload = [
            'country_id' => $this->form_country_id,
            'region_id' => $this->form_region_id,
            'city_id' => $this->form_city_id,
            'street' => $this->form_street ?: null,
            'house' => $this->form_house ?: null,
            'building' => $this->form_building ?: null,
            'apartment' => $this->form_apartment ?: null,
            'postal_code' => $this->form_postal_code ?: null,
            'additional_info' => $this->form_additional_info ?: null,
            'name' => $this->form_name ?: null,
            'address_type' => $this->form_address_type,
            'is_primary' => $this->form_is_primary,
            'is_public' => $this->form_is_public,
            'is_active' => true,
        ];

        if ($this->editingId === null) {
            $payload['addressable_id'] = $owner->id;
            $payload['addressable_type'] = $owner->getMorphClass();
            Address::create($payload);
            $this->notice = __('ui.address.saved');
        } else {
            $address = $this->findOwnedAddress($this->editingId);
            $address->update($payload);
            $this->notice = __('ui.address.updated');
        }

        $this->showForm = false;
        $this->editingId = null;
    }

    public function deleteAddress(int $addressId): void
    {
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);
        $this->findOwnedAddress($addressId)->delete();
        $this->notice = __('ui.address.deleted');
    }

    public function makePrimary(int $addressId): void
    {
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);
        $this->findOwnedAddress($addressId)->setAsPrimary();
        $this->notice = __('ui.address.primary_set');
    }

    public function updatedFormCountryId(): void
    {
        $this->form_region_id = null;
        $this->form_city_id = null;
    }

    public function updatedFormRegionId(): void
    {
        $this->form_city_id = null;
    }

    public function render(): View
    {
        $owner = $this->resolveOwner();
        Gate::authorize('update', $owner);

        $addresses = $owner->addresses()->with(['country', 'region', 'city'])->orderByDesc('is_primary')->orderBy('id')->get();

        $countries = Country::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $regions = $this->form_country_id
            ? Region::query()->where('country_id', $this->form_country_id)->orderBy('name')->get()
            : collect();

        $cities = $this->form_country_id
            ? City::query()->where('country_id', $this->form_country_id)->when($this->form_region_id, fn ($q) => $q->where('region_id', $this->form_region_id))->orderBy('name')->limit(500)->get()
            : collect();

        return view('livewire.music.address-book-panel', [
            'addresses' => $addresses,
            'countries' => $countries,
            'regions' => $regions,
            'cities' => $cities,
            'addressTypes' => Address::TYPES,
        ]);
    }

    /**
     * @return list<string>
     */
    private function allowedKinds(): array
    {
        return ['musician', 'teacher', 'performer', 'studio', 'rehearsal', 'school', 'record_label', 'producer_center', 'shop'];
    }

    private function resolveModelClass(): string
    {
        return match ($this->ownerKind) {
            'musician' => Musician::class,
            'teacher' => Teacher::class,
            'performer' => Peformer::class,
            'studio' => Studio::class,
            'rehearsal' => Rehersal::class,
            'school' => School::class,
            'record_label' => RecordLabel::class,
            'producer_center' => ProducerCenter::class,
            'shop' => Shop::class,
            default => abort(404),
        };
    }

    private function resolveOwner(): Model
    {
        /** @var class-string<Model> $class */
        $class = $this->resolveModelClass();

        return $class::query()->findOrFail($this->ownerId);
    }

    private function findOwnedAddress(int $id): Address
    {
        $owner = $this->resolveOwner();

        return Address::query()
            ->where('addressable_id', $owner->id)
            ->where('addressable_type', $owner->getMorphClass())
            ->whereKey($id)
            ->firstOrFail();
    }

    private function resetFormDefaults(Model $owner): void
    {
        $this->form_country_id = Country::query()->where('is_active', true)->orderBy('sort_order')->value('id');
        $this->form_region_id = null;
        $this->form_city_id = null;
        $this->form_street = '';
        $this->form_house = '';
        $this->form_building = '';
        $this->form_apartment = '';
        $this->form_postal_code = '';
        $this->form_additional_info = '';
        $this->form_name = '';
        $this->form_address_type = match ($this->ownerKind) {
            'studio', 'rehearsal', 'school', 'record_label', 'producer_center', 'shop', 'performer' => 'office',
            default => 'home',
        };
        $this->form_is_primary = $owner->addresses()->count() === 0;
        $this->form_is_public = true;
    }
}
