<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    use HasUuids;

    public const CACHE_KEY = 'app_settings_all';

    public const CACHE_MODELS_KEY = 'app_settings_models';

    public const CACHE_TTL = 86400; // 24 hours

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'is_public',
        'description',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Auto-detect setting type based on variable type.
     */
    public static function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    /**
     * Retrieve all settings cached as a key => casted_value collection.
     *
     * @return Collection<string, mixed>
     */
    public static function getAllCached(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): Collection {
            return self::query()->get()->mapWithKeys(function (self $setting): array {
                return [$setting->key => $setting->getCastedValue()];
            });
        });
    }

    /**
     * Retrieve all settings model records cached and ordered.
     *
     * @return Collection<int, self>
     */
    public static function getAllModels(): Collection
    {
        return Cache::remember(self::CACHE_MODELS_KEY, self::CACHE_TTL, function (): Collection {
            return self::query()->orderBy('group')->orderBy('key')->get();
        });
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_MODELS_KEY);
    }

    /**
     * Retrieve a single setting by key, with optional default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::getAllCached();

        if ($all->has($key)) {
            return $all->get($key);
        }

        return $default;
    }

    /**
     * Set a setting value (create or update).
     */
    public static function set(string $key, mixed $value, ?string $group = null, ?string $type = null, ?bool $isPublic = null): self
    {
        /** @var self $setting */
        $setting = self::query()->firstOrNew(['key' => $key]);

        if ($group !== null) {
            $setting->group = $group;
        } elseif (! $setting->exists) {
            $setting->group = 'general';
        }

        if ($isPublic !== null) {
            $setting->is_public = $isPublic;
        }

        $setting->setCastedValue($value, $type);
        $setting->save();

        self::clearCache();

        return $setting;
    }

    /**
     * Get casted value based on type.
     */
    public function getCastedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $this->value,
            'float', 'double', 'number' => (float) $this->value,
            'array', 'json' => json_decode((string) $this->value, true) ?? [],
            default => (string) $this->value,
        };
    }

    /**
     * Set value serialized based on type.
     */
    public function setCastedValue(mixed $value, ?string $type = null): void
    {
        $resolvedType = $type ?? $this->type ?? self::detectType($value);
        $this->type = $resolvedType;

        if ($value === null) {
            $this->value = null;

            return;
        }

        $this->value = match ($resolvedType) {
            'boolean', 'bool' => $value ? '1' : '0',
            'array', 'json' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
