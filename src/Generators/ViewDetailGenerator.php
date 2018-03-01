<?php

namespace LaraSpells\Generator\Generators;

use LaraSpells\Generator\Stub;
use LaraSpells\Generator\Traits\Concerns\TableUtils;

// @TODO: rename to ViewShowGenerator
class ViewDetailGenerator extends ViewGenerator
{

    use Concerns\TableUtils;

    public function getData()
    {
        $data = parent::getData();
        $tableData = $this->getTableData();
        $data['page_title'] = 'Detail '.$this->tableSchema->getLabel();
        $data['varname'] = $tableData->model_varname;
        $data['primary_key'] = $tableData->primary_key;
        $data['route_list'] = $tableData->route->index;
        $data['route_edit'] = $tableData->route->edit;
        $data['fields'] = $this->generateFields();
        return $data;
    }

    protected function generateFields()
    {
        $tableData = $this->getTableData();
        $fields = $this->tableSchema->getFields();
        $code = $this->makeCodeGenerator();
        foreach($fields as $field) {
            if ($field->isHidden()) {
                continue;
            }
            $column = $field->getColumnName();
            $relation = $field->getRelation();
            if ($relation AND $relation['col_alias']) {
                $column = $relation['col_alias'];
            }
            $stub = new Stub($field->getReadCode());
            $code->addCode("<!-- Column ".$field->getColumnName()." -->");
            $code->addCode($stub->render([
                'label' => $field->getLabel(),
                'field' => $field->toArray(),
                'varname' => $tableData->model_varname,
                'column' => $column,
                'disk' => $field->getUploadDisk(),
            ]));
        }
        return $code->generateCode();
    }

    protected function makeCodeGenerator()
    {
        $code = new CodeGenerator;
        $code->setIndent("  "); // 2 spaces
        return $code;
    }

}
