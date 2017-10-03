<?php

namespace LaraSpells\Generator\Generators;

use LaraSpells\Generator\Schema\Table;
use LaraSpells\Generator\Traits\Concerns\TableUtils;

class UpdateRequestGenerator extends CreateRequestGenerator
{

    use Concerns\TableUtils;

    public function __construct(Table $tableSchema)
    {
        $this->setClassName($tableSchema->getUpdateRequestClass());
        $this->tableSchema = $tableSchema;
        $this->initClass();
        $this->addMethodsFromReflection();
    }
}
