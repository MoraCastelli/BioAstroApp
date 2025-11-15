<?php

namespace App\Services;

use Illuminate\Support\Str;

class SabianoService
{
    private static array $cache = [];

    public static function load(): array
    {
        if (!empty(self::$cache)) return self::$cache;

        $path = storage_path('app/sabianos/sabianos.json');

        if (!file_exists($path)) {
            return self::$cache = [];  // garantizar que siempre sea array
        }

        $json = json_decode(file_get_contents($path), true);

        if (!is_array($json)) return self::$cache = [];

        self::$cache = $json;
        return self::$cache;
    }

    public static function get(string $signo, $grado): ?array
    {
        $data = self::load();

        // Normalizar signo
        $signo = strtolower($signo);
        $signo = str_replace(
            ['á','é','í','ó','ú'],
            ['a','e','i','o','u'],
            $signo
        );
        $signo = ucfirst($signo);

        // Convertir grado
        $grado = intval($grado);

        if ($grado < 1 || $grado > 30) return null;

        if (!isset($data[$signo][$grado])) return null;

        $row = $data[$signo][$grado];

        // Normalizar ruta
        if (!empty($row['imagen'])) {
            $row['imagen'] = str_replace('public/', '', $row['imagen']);
        }

        return $row;
    }

}
