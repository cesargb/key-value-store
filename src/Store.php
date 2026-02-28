<?php

namespace Cesargb\KeyValueStore;

use Cesargb\KeyValueStore\Contracts\Store as StoreContract;
use Cesargb\KeyValueStore\Exceptions\InvalidKeyException;

class Store implements StoreContract
{
    public function __construct(
        private readonly StoreContract $repository,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        return $this->repository->get($key, $default);
    }

    public function set(string $key, mixed $value): bool
    {
        $this->validateKey($key);

        return $this->repository->set($key, $value);
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return $this->repository->delete($key);
    }

    public function clear(): bool
    {
        return $this->repository->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = $this->validateKeys($keys);

        return $this->repository->getMultiple($keys, $default);
    }

    public function setMultiple(iterable $values): bool
    {
        $validatedValues = [];

        foreach ($values as $key => $value) {
            $this->validateKey((string) $key);
            $validatedValues[$key] = $value;
        }

        return $this->repository->setMultiple($validatedValues);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys = $this->validateKeys($keys);

        return $this->repository->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->repository->has($key);
    }

    /**
     * @param  iterable<string>  $keys
     * @return array<string>
     */
    private function validateKeys(iterable $keys): array
    {
        $validated = [];

        foreach ($keys as $key) {
            $this->validateKey($key);
            $validated[] = $key;
        }

        return $validated;
    }

    private function validateKey(string $key): void
    {
        if ($key === '' || preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new InvalidKeyException($key);
        }
    }
}
