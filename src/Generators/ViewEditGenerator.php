<?php

namespace LaraSpells\Generator\Generators;

use LaraSpells\Generator\Stub;
use LaraSpells\Generator\Traits\Concerns\TableUtils;

class ViewEditGenerator extends ViewCreateGenerator
{

    use Concerns\TableUtils;

    public function getData()
    {
        $data = parent::getData();
        $data['page_title'] = 'Edit '.$this->tableSchema->getLabel();
        $data['form'] = [
            'table' => $this->tableSchema->getName(),
            'table_singular' => $this->tableSchema->getSingularName(),
            'id' => $this->getFormId(),
            'attributes' => $this->getFormAttributes(),
            'fields' => $this->getFormFields(),
        ];

        return $data;
    }

    protected function getFormFields()
    {
        $tableData = $this->getTableData();
        $schema = $this->tableSchema;
        $rootSchema = $schema->getRootSchema();
        $modelVarname = $tableData->model_varname;
        $inputableFields = $schema->getInputableFields();
        $includeFields = [];
        foreach($inputableFields as $field) {
            $params = $field->getInputParams();
            $key = $field->getColumnName();
            $params['value'] = "eval(\"\${$modelVarname}['{$key}']\")";
            $view = $rootSchema->getView($field->getInputView());
            $includeFields[] = "@include('{$view}', ".$this->phpify($params, true).")";
        }

        $code = $this->makeCodeGenerator();
        $code->addCode(implode("\n\n", $includeFields));

        return $code->generateCode();
    }

    protected function getActionUrl()
    {
        $tableData = $this->getTableData();
        $modelVarname = $tableData->model_varname;
        $pk = $tableData->primary_key;
        $routeName = $tableData->route->update;
        return "{{ route('{$routeName}', [\${$modelVarname}['{$pk}']]) }}";
    }

    protected function getFormId()
    {
        return "edit-".$this->tableSchema->getSingularName();
    }
}
