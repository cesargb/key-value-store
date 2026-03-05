<?php

namespace Cesargb\KeyValueStore\Tests\Repositories;

use Cesargb\KeyValueStore\Repositories\EloquentStore;
use Cesargb\KeyValueStore\Tests\Models\KeyValue;
use Cesargb\KeyValueStore\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class EloquentStoreTest extends TestCase
{
    private EloquentStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('key_value_store', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
        });

        $this->store = new EloquentStore;
    }

    protected function tearDown(): void
    {
        Capsule::schema()->dropIfExists('key_value_store');

        parent::tearDown();
    }

    public function test_set_and_get(): void
    {
        $this->assertTrue($this->store->set('key', 'value'));
        $this->assertSame('value', $this->store->get('key'));
    }

    public function test_get_returns_default_when_key_not_exists(): void
    {
        $this->assertNull($this->store->get('missing'));
        $this->assertSame('default', $this->store->get('missing', 'default'));
    }

    public function test_set_overwrites_existing_value(): void
    {
        $this->store->set('key', 'first');
        $this->store->set('key', 'second');

        $this->assertSame('second', $this->store->get('key'));
    }

    public function test_delete_existing_key(): void
    {
        $this->store->set('key', 'value');

        $this->assertTrue($this->store->delete('key'));
        $this->assertNull($this->store->get('key'));
    }

    public function test_delete_non_existing_key(): void
    {
        $this->assertFalse($this->store->delete('missing'));
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        $this->store->set('key', 'value');

        $this->assertTrue($this->store->has('key'));
    }

    public function test_has_returns_false_for_missing_key(): void
    {
        $this->assertFalse($this->store->has('missing'));
    }

    public function test_has_returns_true_for_null_value(): void
    {
        $this->store->set('key', null);

        $this->assertTrue($this->store->has('key'));
    }

    public function test_clear(): void
    {
        $this->store->set('a', 1);
        $this->store->set('b', 2);

        $this->assertTrue($this->store->clear());
        $this->assertFalse($this->store->has('a'));
        $this->assertFalse($this->store->has('b'));
    }

    public function test_get_multiple(): void
    {
        $this->store->set('a', 1);
        $this->store->set('b', 2);

        $result = $this->store->getMultiple(['a', 'b', 'c'], 'default');

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 'default'], $result);
    }

    public function test_set_multiple(): void
    {
        $this->assertTrue($this->store->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]));

        $this->assertSame(1, $this->store->get('a'));
        $this->assertSame(2, $this->store->get('b'));
        $this->assertSame(3, $this->store->get('c'));
    }

    public function test_delete_multiple(): void
    {
        $this->store->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertTrue($this->store->deleteMultiple(['a', 'c']));
        $this->assertFalse($this->store->has('a'));
        $this->assertTrue($this->store->has('b'));
        $this->assertFalse($this->store->has('c'));
    }

    public function test_delete_multiple_returns_false_when_key_not_exists(): void
    {
        $this->store->set('a', 1);

        $this->assertFalse($this->store->deleteMultiple(['a', 'missing']));
    }

    public function test_set_and_get_various_types(): void
    {
        $this->store->set('int', 42);
        $this->store->set('float', 3.14);
        $this->store->set('bool', true);
        $this->store->set('array', [1, 2, 3]);
        $this->store->set('null', null);

        $this->assertSame(42, $this->store->get('int'));
        $this->assertSame(3.14, $this->store->get('float'));
        $this->assertTrue($this->store->get('bool'));
        $this->assertSame([1, 2, 3], $this->store->get('array'));
        $this->assertNull($this->store->get('null'));
    }

    public function test_data_persists_in_database(): void
    {
        $this->store->set('persistent', 'data');

        $row = Capsule::table('key_value_store')->where('key', 'persistent')->first();

        $this->assertNotNull($row);
        $this->assertSame('persistent', $row->key);
        $this->assertSame(serialize('data'), $row->value);
    }

    public function test_accepts_custom_model(): void
    {
        $store = new EloquentStore(new KeyValue);

        $this->assertTrue($store->set('custom', 'model'));
        $this->assertSame('model', $store->get('custom'));
    }
}
