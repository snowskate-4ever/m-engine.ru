<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthChannel extends Model
{
    protected $fillable = [
        'name',
        'type',
        'config',
        'is_active',
        'webhook_url'
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
    }
}
