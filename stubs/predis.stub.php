<?php

declare(strict_types=1);

namespace Predis;

interface ClientInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value): mixed;

    /**
     * @param  list<string>  $keys
     */
    public function del(array $keys): int;

    /**
     * @param  array{MATCH:string,COUNT:int}  $options
     * @return array{0:string,1:list<string>}
     */
    public function scan(string $cursor, array $options = []): array;

    /**
     * @param  list<string>  $keys
     * @return list<?string>
     */
    public function mget(array $keys): array;

    /**
     * @param  array<string,string>  $dictionary
     */
    public function mset(array $dictionary): mixed;

    public function exists(string $key): int;
}
