<?php

namespace App\Models\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAddresses
{
    /**
     * Получить все адреса модели
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Получить активные адреса
     */
    public function activeAddresses()
    {
        return $this->addresses()->active()->get();
    }

    /**
     * Получить основной адрес
     */
    public function primaryAddress()
    {
        return $this->addresses()->primary()->active()->first();
    }

    /**
     * Получить адреса определенного типа
     */
    public function addressesByType($type)
    {
        return $this->addresses()->type($type)->active()->get();
    }

    /**
     * Получить адрес для доставки
     */
    public function shippingAddress()
    {
        return $this->addresses()->type('shipping')->active()->first() 
            ?? $this->primaryAddress();
    }

    /**
     * Получить адрес для оплаты
     */
    public function billingAddress()
    {
        return $this->addresses()->type('billing')->active()->first() 
            ?? $this->primaryAddress();
    }

    /**
     * Добавить новый адрес
     */
    public function addAddress(array $data)
    {
        // Если устанавливаем как основной, снимаем флаг у других адресов
        if (isset($data['is_primary']) && $data['is_primary']) {
            $this->addresses()->update(['is_primary' => false]);
        }

        return $this->addresses()->create($data);
    }

    /**
     * Обновить адрес
     */
    public function updateAddress($addressId, array $data)
    {
        $address = $this->addresses()->findOrFail($addressId);

        // Если устанавливаем как основной, снимаем флаг у других адресов
        if (isset($data['is_primary']) && $data['is_primary']) {
            $this->addresses()->where('id', '!=', $addressId)->update(['is_primary' => false]);
        }

        return $address->update($data);
    }

    /**
     * Удалить адрес
     */
    public function removeAddress($addressId)
    {
        $address = $this->addresses()->findOrFail($addressId);
        
        // Если удаляем основной адрес, назначаем новый основной
        if ($address->is_primary) {
            $newPrimary = $this->addresses()
                ->where('id', '!=', $addressId)
                ->active()
                ->first();
            
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        return $address->delete();
    }

    /**
     * Установить адрес как основной
     */
    public function setPrimaryAddress($addressId)
    {
        $this->addresses()->update(['is_primary' => false]);
        
        return $this->addresses()->findOrFail($addressId)->update(['is_primary' => true]);
    }

    /**
     * Проверить, есть ли у модели адреса
     */
    public function hasAddresses(): bool
    {
        return $this->addresses()->active()->exists();
    }

    /**
     * Получить количество адресов
     */
    public function addressCount(): int
    {
        return $this->addresses()->active()->count();
    }

    /**
     * Получить полный адрес по умолчанию
     */
    public function getDefaultAddressAttribute(): ?string
    {
        $address = $this->primaryAddress();
        
        return $address ? $address->full_address : null;
    }

    /**
     * Получить город по умолчанию
     */
    public function getDefaultCityAttribute(): ?string
    {
        $address = $this->primaryAddress();
        
        return $address && $address->city ? $address->city->name : null;
    }

    /**
     * Получить страну по умолчанию
     */
    public function getDefaultCountryAttribute(): ?string
    {
        $address = $this->primaryAddress();
        
        return $address && $address->country ? $address->country->name : null;
    }
}