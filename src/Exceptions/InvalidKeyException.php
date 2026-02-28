<?php

namespace Cesargb\KeyValueStore\Exceptions;

class InvalidKeyException extends \InvalidArgumentException
{
    public function __construct(string $key)
    {
        parent::__construct(
            sprintf('The key "%s" is not valid. Keys must not be empty and must not contain {}()/\@: characters.', $key)
        );
    }
}
