<?php

namespace LaraSpells\Generator;

interface SchemaResolverInterface
{

    /**
     * Resolve LaraSpell schema
     *
     * @param  array $schema
     * @return array
     */
    public function resolve(array $schema);

}
