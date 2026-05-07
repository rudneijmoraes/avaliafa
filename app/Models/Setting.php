<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    /**
     * Busca um valor de configuração.
     */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = static::where('group', $group)->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Define (cria ou atualiza) um valor de configuração.
     */
    public static function set(string $group, string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value]
        );

        Cache::forget("settings.{$group}");
    }

    /**
     * Retorna todas as configurações de um grupo como array associativo.
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("settings.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Salva várias configurações de um grupo de uma vez.
     */
    public static function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value ?? '']
            );
        }

        Cache::forget("settings.{$group}");
    }
}
