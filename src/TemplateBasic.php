<?php

namespace LaraSpell;

use LaraSpell\Commands\SchemaBasedCommand;

class TemplateBasic extends Template
{

    public function __construct(SchemaBasedCommand $command)
    {
        parent::__construct($command);
        $this->directory = realpath(__DIR__.'/../template');
    }

}
