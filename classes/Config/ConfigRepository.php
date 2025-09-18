<?php
declare(strict_types=1);

namespace PU239\Config;

final class ConfigRepository
{
    /** @var array<string, mixed> */
    private array $items = [];

    /** @param array<string, mixed> $base */
    public function __construct(array $base = [])
    {
        $this->items = $base;
    }

    /** @return mixed */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @param array<string, mixed> $arr */
    public function merge(array $arr): void
    {
        $this->items = self::arrayMergeRecursiveDistinct($this->items, $arr);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     *
     * @return array<string, mixed>
     */
    private static function arrayMergeRecursiveDistinct(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            if (is_array($value) && isset($a[$key]) && is_array($a[$key])) {
                $a[$key] = self::arrayMergeRecursiveDistinct($a[$key], $value);
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}
