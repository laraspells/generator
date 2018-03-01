<?php

namespace LaraSpells\Generator\Generators;

use LaraSpells\Generator\Stub;
use LaraSpells\Generator\Traits\Concerns\TableUtils;

// @TODO: rename to ViewIndexGenerator
class ViewListGenerator extends ViewGenerator
{

    use Concerns\TableUtils;

    public function getData()
    {
        $data = parent::getData();
        $data['page_title'] = 'List '.$this->tableSchema->getLabel();
        $data['route_create'] = $this->tableSchema->getRouteCreateName();
        $data['table'] = [
            'label' => $this->tableSchema->getLabel(),
            'id' => $this->getTableId(),
            'html' => $this->generateHtmlTable(),
            'pagination' => $this->generateHtmlPagination(),
        ];

        return $data;
    }

    protected function getTableId()
    {
        return "table-".$this->tableSchema->getName();
    }

    protected function generateHtmlTable()
    {
        $tableId = $this->getTableId();
        $tableData = $this->getTableData();
        $inputableFields = $this->tableSchema->getInputableFields();
        $theads = [];
        $bodys = [];
        foreach($inputableFields as $field) {
            if ($field->isHidden() === true) {
                continue;
            }

            $col = $field->getColumnName();
            $tableCode = $field->getTableCode() ?: '{{ ${? varname ?}[\'{? column ?}\'] }}';
            $label = $field->getLabel();
            $relation = $field->getRelation();
            if ($relation AND $relation['col_alias']) {
                $col = $relation['col_alias'];
            }
            $stub = new Stub($tableCode);
            $tableCode = $stub->render([
                'field' => $field->toArray(),
                'disk' => $field->getUploadDisk(),
                'column' => $col,
                'varname' => $tableData->model_varname
            ]);

            $theads[] = "<th class='column-{$col}'>{$label}</th>";
            $tbodys[] = "<td class='column-{$col}'>{$tableCode}</td>";
        }

        $countColumns = count($tbodys) + 2;

        $code = $this->makeCodeGenerator();
        $code->addCode('
            <table id="'.$tableId.'" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th width="20" class="text-center column-number">No</th>
                        '.implode("\n", $theads).'
                        <th class="text-center column-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$pagination->count())
                    <tr>
                        <td colspan="'.$countColumns.'" class="text-center">
                            Records empty.
                        </td>
                    </tr>
                    @endif
                    @foreach($pagination->items() as $i => $'.$tableData->model_varname.')
                    <tr>
                        <td class="text-center column-number">{{ $pagination->firstItem() + $i }}</td>
                        '.implode("\n", $tbodys).'
                        <td width="200" class="text-center column-action">
                            <a class="btn btn-sm btn-edit btn-default" href="{{ route(\''.$tableData->route->show.'\', [$'.$tableData->model_varname.'->getKey()]) }}">Show</a>
                            <a class="btn btn-sm btn-edit btn-primary" href="{{ route(\''.$tableData->route->edit.'\', [$'.$tableData->model_varname.'->getKey()]) }}">Edit</a>
                            <form style="display:inline;" onclick="return confirm(\'Are you sure to delete this data?\')" method="POST" action="{{ route(\''.$tableData->route->destroy.'\', [$'.$tableData->model_varname.'->getKey()]) }}">
                                {!! csrf_field() !!}
                                {{ method_field("DELETE") }}
                                <button class="btn btn-sm btn-delete btn-danger" href="">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        ');

        return $code->generateCode();
    }

    protected function generateHtmlPagination()
    {
        $tableData = $this->getTableData();
        $code = $this->makeCodeGenerator();
        $code->addCode('{!! $pagination->links() !!}');

        return $code->generateCode();
    }

    protected function makeCodeGenerator()
    {
        $code = new CodeGenerator;
        $code->setIndent("  "); // 2 spaces
        return $code;
    }

}
