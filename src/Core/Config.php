<?php

namespace App\Core;

class Config
{
    private static array $config = [];

    public static function load(string $path): void
    {
        $files = glob($path . '/*.php');
        foreach ($files as $file) {
            $config = require $file;
            foreach ($config as $key => $value) {
                self::$config[$key] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $target = &self::$config;

        foreach ($keys as $k) {
            if (!isset($target[$k])) {
                $target[$k] = [];
            }
            $target = &$target[$k];
        }

        $target = $value;
    }

    public static function all(): array
    {
        return self::$config;
    }
}
