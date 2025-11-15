<?php

namespace App\Services;

class LunacionService
{
    public static function all(): array
    {
        return config('fases_lunacion');
    }

    public static function get(string $fase): ?array
    {
        $fases = self::all();
        return $fases[$fase] ?? null;
    }

    public static function getPlaneta(string $fase): ?string
    {
        return self::get($fase)['planeta'] ?? null;
    }
}
