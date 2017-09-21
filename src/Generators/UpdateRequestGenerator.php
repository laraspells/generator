<?php

namespace LaraSpells\Generators;

use LaraSpells\Schema\Table;
use LaraSpells\Traits\Concerns\TableUtils;

class UpdateRequestGenerator extends CreateRequestGenerator
{

    use Concerns\TableUtils;

    protected $tableSchema;

    public function __construct(Table $tableSchema)
    {
        $this->setClassName($tableSchema->getUpdateRequestClass());
        $this->tableSchema = $tableSchema;
        $this->initClass();
        $this->addMethodsFromReflection();
    }
}
