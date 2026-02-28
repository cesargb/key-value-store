<?php

namespace Cesargb\KeyValueStore\Repositories;

use Cesargb\KeyValueStore\Contracts\Store;
use Illuminate\Database\Eloquent\Model;

class EloquentStore implements Store
{
    public function __construct(
        private readonly Model $model,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $record = $this->model->newQuery()->find($key);

        if ($record === null) {
            return $default;
        }

        return unserialize($record->getAttribute('value'), ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value): bool
    {
        return $this->model->newQuery()->toBase()->updateOrInsert(
            [$this->model->getKeyName() => $key],
            ['value' => serialize($value)]
        );
    }

    public function delete(string $key): bool
    {
        return $this->model->newQuery()
            ->where($this->model->getKeyName(), $key)
            ->delete() > 0;
    }

    public function clear(): bool
    {
        $this->model->newQuery()->toBase()->delete();

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keysArray = [];

        foreach ($keys as $key) {
            $keysArray[] = $key;
        }

        $records = $this->model->newQuery()
            ->whereIn($this->model->getKeyName(), $keysArray)
            ->pluck('value', $this->model->getKeyName());

        $result = [];

        foreach ($keysArray as $key) {
            if ($records->has($key)) {
                $result[$key] = unserialize($records->get($key), ['allowed_classes' => false]);
            } else {
                $result[$key] = $default;
            }
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
        $keysArray = [];

        foreach ($keys as $key) {
            $keysArray[] = $key;
        }

        $deleted = $this->model->newQuery()
            ->whereIn($this->model->getKeyName(), $keysArray)
            ->delete();

        return $deleted === count($keysArray);
    }

    public function has(string $key): bool
    {
        return $this->model->newQuery()
            ->where($this->model->getKeyName(), $key)
            ->exists();
    }
}
