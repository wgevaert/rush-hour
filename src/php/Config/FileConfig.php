<?php

namespace RushHour\Config;

use RuntimeException;

class FileConfig implements Config
{
    private array $config = [];

    public function __construct(?string $filePath = null)
    {
        $this->config = $this->loadConfig($filePath);
    }

    public function loadConfig(?string $filePath = null): array
    {
        if ($filePath === null) {
            $filePath = __DIR__ . '/../../../config.json';
        }
        if (!file_exists($filePath)) {
            throw new RuntimeException("Config file not found at $filePath");
        }
        $json = file_get_contents($filePath);
        $config = json_decode($json, true);
        if ($config === null) {
            throw new RuntimeException("Failed to parse config file at $filePath");
        }
        return $config;
    }

    public function get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
}
