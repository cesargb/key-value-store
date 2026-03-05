<?php

namespace Cesargb\KeyValueStore\Laravel;

use Cesargb\KeyValueStore\Contracts\Store as StoreContract;
use Cesargb\KeyValueStore\Models\KeyValue;
use Cesargb\KeyValueStore\Repositories\EloquentStore;
use Cesargb\KeyValueStore\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class KeyValueStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/key-value-store.php', 'key-value-store');

        $this->app->singleton(EloquentStore::class, function ($app): EloquentStore {
            $modelClass = (string) $app['config']->get('key-value-store.model', KeyValue::class);
            $table = (string) $app['config']->get('key-value-store.table', 'key_value_store');

            /** @var Model $model */
            $model = new $modelClass;
            $model->setTable($table);

            return new EloquentStore($model);
        });

        $this->app->singleton(Store::class, function ($app): Store {
            return new Store($app->make(EloquentStore::class));
        });

        $this->app->alias(Store::class, StoreContract::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../config/key-value-store.php' => config_path('key-value-store.php'),
        ], 'key-value-store-config');

        $this->publishes([
            __DIR__.'/../../database/migrations/create_key_value_store_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_key_value_store_table.php'),
        ], 'key-value-store-migrations');
    }
}
