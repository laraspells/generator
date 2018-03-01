<?php

namespace LaraSpells\Generator\Generators;

use LaraSpells\Generator\Schema\Table;
use LaraSpells\Generator\Traits\Concerns\TableUtils;
use LaraSpells\Generator\Stub;

class ViewGenerator extends BaseGenerator
{
    use Concerns\TableUtils;

    protected $content = "";

    public function __construct(Table $tableSchema, $stubContent = '')
    {
        $this->tableSchema = $tableSchema;
        $this->setContent($stubContent);
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getData()
    {
        $tableSchema = $this->tableSchema;
        $viewNamespace = $this->tableSchema->getRootSchema()->getViewNamespace();
        return [
            'schema' => $tableSchema->getRootSchema()->toArray(),
            'table' => $tableSchema->toArray(),
            'view_namespace' => $viewNamespace? $viewNamespace.'::' : '',
            'route_list' => $tableSchema->getRouteIndexName(),
            'route_create' => $tableSchema->getRouteCreateName(),
            'route_edit' => $tableSchema->getRouteEditName(),
            'route_delete' => $tableSchema->getRouteDestroyName(),
        ];
    }

    public function generateLines()
    {
        $stub = new Stub($this->getContent());
        $data = $this->getData();
        $result = $stub->render($data);

        $lines = preg_split("/\n\r?/", $result);

        return $lines;
    }

}
