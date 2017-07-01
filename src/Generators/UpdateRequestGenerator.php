<?php

namespace LaraSpell\Generators;

use LaraSpell\Schema\Table;
use LaraSpell\Traits\TableDataGetter;

class UpdateRequestGenerator extends CreateRequestGenerator
{

    use TableDataGetter;

    protected $tableSchema;

    public function __construct(Table $tableSchema)
    {
        $this->setClassName($tableSchema->getUpdateRequestClass());
        $this->tableSchema = $tableSchema;
        $this->initClass();
        $this->addMethodsFromReflection();
    }
}
