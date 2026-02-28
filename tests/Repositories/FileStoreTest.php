<?php

namespace Cesargb\KeyValueStore\Tests\Repositories;

use Cesargb\KeyValueStore\Repositories\FileStore;
use Cesargb\KeyValueStore\Tests\TestCase;

class FileStoreTest extends TestCase
{
    private FileStore $store;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'file-store-test-'.uniqid();
        $this->store = new FileStore($this->directory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);

        parent::tearDown();
    }

    public function test_constructor_creates_directory(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'file-store-create-'.uniqid();

        $this->assertDirectoryDoesNotExist($dir);

        new FileStore($dir);

        $this->assertDirectoryExists($dir);

        $this->removeDirectory($dir);
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

    public function test_key_with_special_characters_is_encoded_safely(): void
    {
        $this->store->set('key with spaces', 'value1');
        $this->store->set('key&special=chars', 'value2');

        $this->assertSame('value1', $this->store->get('key with spaces'));
        $this->assertSame('value2', $this->store->get('key&special=chars'));
    }

    public function test_dot_keys_are_encoded(): void
    {
        $this->store->set('.', 'dot');
        $this->store->set('..', 'dotdot');
        $this->store->set('.hidden', 'hidden');

        $this->assertSame('dot', $this->store->get('.'));
        $this->assertSame('dotdot', $this->store->get('..'));
        $this->assertSame('hidden', $this->store->get('.hidden'));
    }

    public function test_each_key_creates_a_file(): void
    {
        $this->store->set('alpha', 'value');

        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*.dat');

        $this->assertNotFalse($files);
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('alpha.dat', $files[0]);
    }

    public function test_clear_removes_all_files(): void
    {
        $this->store->set('a', 1);
        $this->store->set('b', 2);

        $this->store->clear();

        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*.dat');

        $this->assertNotFalse($files);
        $this->assertCount(0, $files);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
