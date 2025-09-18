<?php
declare(strict_types=1);

namespace PU239\Config;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class ConfigLoader
{
    /** @var list<string> */
    private array $excludedBasenames = ['definitions.php'];

    public function load(string $baseDir, string $environment = 'local'): ConfigRepository
    {
        $repository = new ConfigRepository();
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);

        foreach ($this->gatherConfigFiles($baseDir) as $file) {
            $repository->merge($this->requireConfigFile($file));
        }

        $envDir = $baseDir . DIRECTORY_SEPARATOR . 'env' . DIRECTORY_SEPARATOR . $environment;
        foreach ($this->gatherConfigFiles($envDir, false) as $file) {
            $repository->merge($this->requireConfigFile($file));
        }

        return $repository;
    }

    /**
     * @return list<string>
     */
    private function gatherConfigFiles(string $directory, bool $excludeEnv = true): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $basename = $file->getBasename();
            if (str_ends_with($basename, '.dist.php')) {
                continue;
            }
            if (in_array($basename, $this->excludedBasenames, true)) {
                continue;
            }
            if ($excludeEnv && str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'env' . DIRECTORY_SEPARATOR)) {
                continue;
            }
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireConfigFile(string $file): array
    {
        $data = require $file;
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Config file %s must return an associative array.', $file));
        }

        return $data;
    }
}
