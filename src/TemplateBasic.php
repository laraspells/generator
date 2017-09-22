<?php

namespace LaraSpells\Generator;

use LaraSpells\Generator\Commands\SchemaBasedCommand;

class TemplateBasic extends Template
{

    public function __construct(SchemaBasedCommand $command)
    {
        parent::__construct($command);
        $this->directory = realpath(__DIR__.'/../template');
    }

}
