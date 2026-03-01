# Key Value Store

A simple and flexible key-value store library for PHP. It provides a common API with multiple backends: in-memory array, file storage, Redis (`ext-redis`), Predis, and Eloquent.

## Install

```bash
composer require cesargb/key-value-store
```

Requirements:
- PHP `^8.4`

Optional dependencies:
- `ext-redis` for `RedisStore`
- `predis/predis` for `PredisStore`
- `illuminate/database` for `EloquentStore`

## Usage

### List of stores

- `ArrayStore`: in-memory storage (resets when process ends)
- `FileStore`: file-based storage in a local directory
- `RedisStore`: Redis storage using the `ext-redis` extension
- `PredisStore`: Redis storage using `predis/predis`
- `EloquentStore`: database storage through an Eloquent model

### Basic usage

```php
<?php

use Cesargb\KeyValueStore\Store;
use Cesargb\KeyValueStore\Repositories\ArrayStore;

$store = new Store(new ArrayStore());

$store->set('name', 'Cesar');
echo $store->get('name'); // Cesar

var_dump($store->has('name')); // true
$store->delete('name');
var_dump($store->has('name')); // false
```

### Multiple keys

```php
<?php

use Cesargb\KeyValueStore\Store;
use Cesargb\KeyValueStore\Repositories\ArrayStore;

$store = new Store(new ArrayStore());

$store->setMultiple([
    'a' => 1,
    'b' => 2,
]);

$values = $store->getMultiple(['a', 'b', 'c'], 'default');
// ['a' => 1, 'b' => 2, 'c' => 'default']

$store->deleteMultiple(['a', 'b']);
```

### Use a different repository

```php
<?php

use Cesargb\KeyValueStore\Store;
use Cesargb\KeyValueStore\Repositories\FileStore;
use Cesargb\KeyValueStore\Repositories\RedisStore;
use Cesargb\KeyValueStore\Repositories\PredisStore;
use Cesargb\KeyValueStore\Repositories\EloquentStore;

// File repository
$store = new Store(new FileStore(__DIR__.'/storage/kv'));

// Redis repository (ext-redis)
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$store = new Store(new RedisStore($redis, prefix: 'kv_'));

// Predis repository
$predis = new Predis\Client(['host' => '127.0.0.1', 'port' => 6379]);
$store = new Store(new PredisStore($predis, prefix: 'kv_'));

// Eloquent repository
// $model must be an Eloquent model with key and value columns.
$store = new Store(new EloquentStore($model));
```

Key rules:
- Keys cannot be empty.
- Keys cannot contain: `{ } ( ) / \ @ :`
