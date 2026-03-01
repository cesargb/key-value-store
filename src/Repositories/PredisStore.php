<?php

namespace Cesargb\KeyValueStore\Repositories;

use Cesargb\KeyValueStore\Contracts\Store;

class PredisStore implements Store
{
    public function __construct(
        private readonly \Predis\ClientInterface $client,
        private readonly string $prefix = '',
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->execute(fn (): ?string => $this->client->get($this->prefix.$key));

        if ($value === null) {
            return $default;
        }

        return unserialize($value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value): bool
    {
        return $this->execute(function () use ($key, $value): bool {
            $result = $this->client->set($this->prefix.$key, serialize($value));

            return $this->isOkResponse($result);
        });
    }

    public function delete(string $key): bool
    {
        return $this->execute(fn (): bool => $this->client->del([$this->prefix.$key]) > 0);
    }

    public function clear(): bool
    {
        return $this->execute(function (): bool {
            $cursor = '0';
            $pattern = $this->prefix.'*';

            do {
                /** @var array{0:string,1:list<string>} $result */
                $result = $this->client->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
                $cursor = $result[0];
                $keys = $result[1];

                if ($keys !== []) {
                    $this->client->del($keys);
                }
            } while ($cursor !== '0');

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

            /** @var list<?string> $values */
            $values = $this->client->mget($prefixedKeys);

            $result = [];

            foreach ($keysArray as $i => $key) {
                if ($values[$i] === null) {
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

            $result = $this->client->mset($pairs);

            return $this->isOkResponse($result);
        });
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->execute(function () use ($keys): bool {
            $keysArray = [];

            foreach ($keys as $key) {
                $keysArray[] = $this->prefix.$key;
            }

            if ($keysArray === []) {
                return true;
            }

            $deleted = $this->client->del($keysArray);

            return $deleted === count($keysArray);
        });
    }

    public function has(string $key): bool
    {
        return $this->execute(fn (): bool => (bool) $this->client->exists($this->prefix.$key));
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
        } catch (\Throwable) {
            return $callback();
        }
    }

    private function isOkResponse(mixed $response): bool
    {
        if ($response === true) {
            return true;
        }

        if (is_string($response)) {
            return strtoupper($response) === 'OK';
        }

        if (is_object($response) && method_exists($response, '__toString')) {
            return strtoupper((string) $response) === 'OK';
        }

        return false;
    }
}
