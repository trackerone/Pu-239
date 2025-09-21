<?php
declare(strict_types=1);

namespace Pu239\Config;

/**
 * Simple dot-key config repository with typed accessors and safe defaults.
 */
final class ConfigRepository
{
    /** @var array<string,mixed> */
    private array $items = [];

    /**
     * @param array<string,mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Merge (shallow/deep) new config values, later values win.
     *
     * @param array<string,mixed> $items
     */
    public function merge(array $items): void
    {
        $this->items = array_replace_recursive($this->items, $items);
    }

    /**
     * Get a config value by dotted key, or $default if missing.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $default;
        }
        $segments = explode('.', $key);
        $value = $this->items;
        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) {
                return $default;
            }
            $value = $value[$seg];
        }
        return $value;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->get($key, $default);
        return is_string($v) ? $v : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $v = $this->get($key, $default);
        if (is_bool($v)) {
            return $v;
        }
        if (is_numeric($v)) {
            return (bool) $v;
        }
        if (is_string($v)) {
            $lv = strtolower($v);
            if (in_array($lv, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lv, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        return $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->get($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    /**
     * @return array<int|string,mixed>
     */
    public function arr(string $key, array $default = []): array
    {
        $v = $this->get($key, $default);
        return is_array($v) ? $v : $default;
    }
}
