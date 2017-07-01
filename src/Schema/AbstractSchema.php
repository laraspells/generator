<?php

namespace LaraSpell\Schema;

use UnexpectedValueException;

abstract class AbstractSchema
{

    protected $schema;

    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function get($key)
    {
        return array_get($this->schema, $key);
    }

    public function toArray()
    {
        return $this->schema;
    }

}
