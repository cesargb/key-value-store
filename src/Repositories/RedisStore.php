<?php

namespace Cesargb\KeyValueStore\Repositories;

use Cesargb\KeyValueStore\Contracts\Store;

class RedisStore implements Store
{
    private \Redis $redis;

    public function __construct(
        \Redis $redis,
        private readonly string $prefix = '',
    ) {
        $this->redis = $redis;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->execute(fn (): string|false => $this->redis->get($this->prefix.$key));

        if ($value === false) {
            return $default;
        }

        return unserialize($value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value): bool
    {
        return $this->execute(fn (): bool => $this->redis->set($this->prefix.$key, serialize($value)));
    }

    public function delete(string $key): bool
    {
        return $this->execute(fn (): bool => $this->redis->del($this->prefix.$key) > 0);
    }

    public function clear(): bool
    {
        return $this->execute(function (): bool {
            $iterator = null;
            $pattern = $this->prefix.'*';

            do {
                /** @var list<string>|false $keys */
                $keys = $this->redis->scan($iterator, $pattern, 100);

                if ($keys !== false && $keys !== []) {
                    $this->redis->del(...$keys);
                }
            } while ($iterator !== 0 && $iterator !== false);

            return true;
        });
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->execute(function () use ($keys, $default): array {
            $keysArray = [];

            foreach ($keys as $key) {
                $keysArray[] = $key;
            }

            $prefixedKeys = array_map(fn (string $key): string => $this->prefix.$key, $keysArray);

            /** @var list<false|string> $values */
            $values = $this->redis->mGet($prefixedKeys);

            $result = [];

            foreach ($keysArray as $i => $key) {
                if ($values[$i] === false) {
                    $result[$key] = $default;
                } else {
                    $result[$key] = unserialize($values[$i], ['allowed_classes' => false]);
                }
            }

            return $result;
        });
    }

    public function setMultiple(iterable $values): bool
    {
        return $this->execute(function () use ($values): bool {
            $pairs = [];

            foreach ($values as $key => $value) {
                $pairs[$this->prefix.$key] = serialize($value);
            }

            return $this->redis->mSet($pairs);
        });
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->execute(function () use ($keys): bool {
            $keysArray = [];

            foreach ($keys as $key) {
                $keysArray[] = $this->prefix.$key;
            }

            $deleted = $this->redis->del(...$keysArray);

            return $deleted === count($keysArray);
        });
    }

    public function has(string $key): bool
    {
        return $this->execute(fn (): bool => (bool) $this->redis->exists($this->prefix.$key));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\RedisException) {
            return $callback();
        }
    }
}
