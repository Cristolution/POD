<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // ------------------------------------------------------------------
    // Static helpers (key-value convenience)
    // ------------------------------------------------------------------

    /**
     * Read a setting as a string. Returns $default if not set.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::key($key)->value('value') ?? $default;
    }

    /**
     * Set or update a setting. Creates the row if missing.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Read a setting as a bool. Falsy strings ("false", "0", "") → false.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return ! in_array(strtolower($value), ['false', '0', '', 'no'], true);
    }

    /**
     * Read a setting as an int.
     */
    public static function int(string $key, int $default = 0): int
    {
        return (int) static::get($key, (string) $default);
    }
}
