<?php

namespace LaraSpell\Generators;

use LaraSpell\Schema\Table;
use LaraSpell\Stub;

class ViewGenerator extends BaseGenerator
{

    protected $content = "";

    protected $tableSchema;

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
            'route_list' => $tableSchema->getRouteListName(),
            'route_create' => $tableSchema->getRouteCreateName(),
            'route_edit' => $tableSchema->getRouteEditName(),
            'route_delete' => $tableSchema->getRouteDeleteName(),
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
