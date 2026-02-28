<?php

namespace Cesargb\KeyValueStore\Repositories;

use Cesargb\KeyValueStore\Contracts\Store;

class FileStore implements Store
{
    private readonly string $directory;

    public function __construct(string $directory)
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $realpath = realpath($directory);

        if ($realpath === false) {
            throw new \RuntimeException(sprintf('Unable to resolve directory path: %s', $directory));
        }

        $this->directory = $realpath;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->keyToPath($key);

        if (! file_exists($path)) {
            return $default;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return $default;
        }

        return unserialize($content, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value): bool
    {
        $path = $this->keyToPath($key);

        return file_put_contents($path, serialize($value), LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $path = $this->keyToPath($key);

        if (! file_exists($path)) {
            return false;
        }

        return unlink($path);
    }

    public function clear(): bool
    {
        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*.dat');

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (! $this->set($key, $value)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (! $this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    public function has(string $key): bool
    {
        return file_exists($this->keyToPath($key));
    }

    private function keyToPath(string $key): string
    {
        $encoded = str_replace('.', '%2E', rawurlencode($key));

        $path = $this->directory.DIRECTORY_SEPARATOR.$encoded.'.dat';

        $this->assertSafePath($path);

        return $path;
    }

    private function assertSafePath(string $path): void
    {
        $dir = realpath(dirname($path));

        if ($dir === false || ! str_starts_with($dir, $this->directory)) {
            throw new \RuntimeException(
                sprintf('Access denied: path "%s" is outside the store directory.', $path)
            );
        }
    }
}
