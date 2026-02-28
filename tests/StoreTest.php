<?php

namespace Cesargb\KeyValueStore\Tests;

use Cesargb\KeyValueStore\Exceptions\InvalidKeyException;
use Cesargb\KeyValueStore\Repositories\ArrayStore;
use Cesargb\KeyValueStore\Store;
use PHPUnit\Framework\Attributes\DataProvider;

class StoreTest extends TestCase
{
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new Store(new ArrayStore);
    }

    // --- Delegation tests ---

    public function test_set_and_get(): void
    {
        $this->assertTrue($this->store->set('key', 'value'));
        $this->assertSame('value', $this->store->get('key'));
    }

    public function test_get_returns_default_when_key_not_exists(): void
    {
        $this->assertNull($this->store->get('missing'));
        $this->assertSame('fallback', $this->store->get('missing', 'fallback'));
    }

    public function test_delete(): void
    {
        $this->store->set('key', 'value');

        $this->assertTrue($this->store->delete('key'));
        $this->assertFalse($this->store->has('key'));
    }

    public function test_has(): void
    {
        $this->assertFalse($this->store->has('key'));

        $this->store->set('key', 'value');

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
        $this->store->set('x', 10);
        $this->store->set('y', 20);

        $result = $this->store->getMultiple(['x', 'y', 'z'], 'none');

        $this->assertSame(['x' => 10, 'y' => 20, 'z' => 'none'], $result);
    }

    public function test_set_multiple(): void
    {
        $this->assertTrue($this->store->setMultiple(['a' => 1, 'b' => 2]));

        $this->assertSame(1, $this->store->get('a'));
        $this->assertSame(2, $this->store->get('b'));
    }

    public function test_delete_multiple(): void
    {
        $this->store->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertTrue($this->store->deleteMultiple(['a', 'c']));
        $this->assertFalse($this->store->has('a'));
        $this->assertTrue($this->store->has('b'));
        $this->assertFalse($this->store->has('c'));
    }

    // --- Key validation tests ---

    public function test_get_throws_on_empty_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->get('');
    }

    public function test_set_throws_on_empty_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->set('', 'value');
    }

    public function test_delete_throws_on_empty_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->delete('');
    }

    public function test_has_throws_on_empty_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->has('');
    }

    public static function reservedCharactersProvider(): array
    {
        return [
            'curly open' => ['invalid{key'],
            'curly close' => ['invalid}key'],
            'paren open' => ['invalid(key'],
            'paren close' => ['invalid)key'],
            'slash' => ['invalid/key'],
            'backslash' => ['invalid\\key'],
            'at sign' => ['invalid@key'],
            'colon' => ['invalid:key'],
        ];
    }

    #[DataProvider('reservedCharactersProvider')]
    public function test_set_throws_on_reserved_characters(string $key): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->set($key, 'value');
    }

    #[DataProvider('reservedCharactersProvider')]
    public function test_get_throws_on_reserved_characters(string $key): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->get($key);
    }

    // --- Multiple key validation tests ---

    public function test_get_multiple_throws_on_invalid_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->getMultiple(['valid', 'invalid{key']);
    }

    public function test_set_multiple_throws_on_invalid_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->setMultiple(['valid' => 1, 'invalid@key' => 2]);
    }

    public function test_delete_multiple_throws_on_invalid_key(): void
    {
        $this->expectException(InvalidKeyException::class);

        $this->store->deleteMultiple(['valid', 'invalid:key']);
    }

    public function test_set_multiple_does_not_persist_if_validation_fails(): void
    {
        try {
            $this->store->setMultiple(['good' => 1, 'bad@key' => 2]);
        } catch (InvalidKeyException) {
            // expected
        }

        $this->assertFalse($this->store->has('good'));
    }
}
