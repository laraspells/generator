<?php

namespace LaraSpells\Generator\Generators;

use LaraSpells\Generator\Schema\Field;
use LaraSpells\Generator\Schema\Table;
use LaraSpells\Generator\Stub;
use LaraSpells\Generator\Traits\Concerns\TableUtils;

class ControllerGenerator extends ClassGenerator
{
    use Concerns\TableUtils;

    const CLASS_REQUEST = 'Illuminate\Http\Request';
    const CLASS_RESPONSE = 'Illuminate\Http\Response';

    protected $tableSchema;

    public function __construct(Table $tableSchema)
    {
        parent::__construct($tableSchema->getControllerClass());
        $this->setTableSchema($tableSchema);
        $this->setParentClass('App\Http\Controllers\Controller');
        $this->useClass(static::CLASS_REQUEST);
        $this->initClass();
        $this->addMethodsFromReflection();
        $this->addMethodsFormOptions();
    }

    protected function initClass()
    {
        $models = $this->getRequiredModels();
        foreach($models as $varName => $model) {
            $label = ucfirst(snake_case($varName, ' '));
            $this->addProperty($varName, $model, 'protected', null, $label.' model');
        }
        $this->setDocblock(function($docblock) {
            $authorName = $this->getTableSchema()->getRootSchema()->getAuthorName();
            $authorEmail = $this->getTableSchema()->getRootSchema()->getAuthorEmail();
            $docblock->addText("Generated by LaraSpell");
            $docblock->addAnnotation("author", "{$authorName} <{$authorEmail}>");
            $docblock->addAnnotation("created", date('r'));
        });
    }

    protected function setMethodConstruct(MethodGenerator $method)
    {
        $models = $this->getRequiredModels();
        $method->setDocblock(function($docblock) use ($models) {
            $docblock->addText("Constructor");
            foreach($models as $varName => $model) {
                $docblock->addParam($varName, $model);
            }
        });
        foreach($models as $varName => $model) {
            $method->addArgument($varName, $model);
        }

        $codeSetModels = implode($this->getNewLine(), array_map(function($varName) {
            return "\$this->{$varName} = \${$varName};";
        }, array_keys($models)));
        $method->appendCode($codeSetModels, "set-models");
    }

    protected function setMethodPageList(MethodGenerator $method)
    {
        $data = $this->getTableData();
        $recordsVarName = $data->table_name;
        $searchables = $this->getTableSchema()->getSearchableFields();

        $method->addArgument('request', static::CLASS_REQUEST);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Display list {$data->table_name}");
            $docblock->addParam('request', static::CLASS_REQUEST);
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $method->appendCode("
            \$limit = (int) \$request->get('limit') ?: 10;
            \$keyword = \$request->get('keyword');
        ", "preparation");

        $method->nl();

        $method->appendCode("\$query = \$this->{$data->model->varname}->query();", "initialize-query");

        if (count($searchables)) {
            $searchQuery = [];
            foreach(array_values($searchables) as $i => $field) {
                $column = $field->getColumnName();
                $queryMethod = ($i == 0) ? "where" : "orWhere";
                $searchQuery[] = "\$query->{$queryMethod}('{$column}', 'like', \"%{\$keyword}%\");";
            }
            $searchQuery = implode("\n", $searchQuery);
            $method->appendCode("
                if (\$keyword) {
                    \$query->where(function(\$query) use (\$keyword) {
                        {$searchQuery}
                    });
                }
            ");
            $method->nl();
        }

        $method->appendCode("
            \$data['title'] = 'List {$data->label}';
            \$data['{$recordsVarName}'] = \$query->paginate(\$limit);

            return view('{$data->view->page_list}', \$data);
        ");
    }

    protected function setMethodPageDetail(MethodGenerator $method)
    {
        $data = $this->getTableData();
        $method->addArgument('request', static::CLASS_REQUEST);
        $method->addArgument($data->primary_varname);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Show detail {$data->model_varname}");
            $docblock->addParam('request', static::CLASS_REQUEST);
            $docblock->addParam($data->primary_varname, 'string');
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $view = $data->view->page_detail;
        $initModelCode = $this->getInitModelCode();
        $method->appendCode($initModelCode);
        $method->nl();
        $method->appendCode("\$data['title'] = 'Detail {$data->label}';");
        $method->appendCode("\$data['{$data->model_varname}'] = \${$data->model_varname};");
        $method->nl();
        $method->appendCode("return view('{$view}', \$data);");
    }

    protected function setMethodFormCreate(MethodGenerator $method)
    {
        $fieldsHasRelation = $this->getInputableFieldsHasRelation();
        $data = $this->getTableData();
        $method->addArgument('request', static::CLASS_REQUEST);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Display form create {$data->model_varname}");
            $docblock->addParam('request', static::CLASS_REQUEST);
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $method->appendCode("\$data['title'] = 'Form Create {$data->label}';");
        foreach($fieldsHasRelation as $field) {
            $relation = $field->getRelation();
            $varName = $relation['var_name'];
            $methodName = 'get'.ucfirst(camel_case($varName));
            $method->appendCode("\$data['{$varName}'] = \$this->{$methodName}();");
        }
        $method->nl();
        $method->appendCode("return view('{$data->view->form_create}', \$data);");
    }

    protected function setMethodPostCreate(MethodGenerator $method)
    {
        $data = $this->getTableData();
        $method->addArgument('request', $data->request->class_create_with_namespace);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Insert new {$data->model_varname}");
            $docblock->addParam('request', $data->request->class_create_with_namespace);
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $inputFiles = $this->getTableSchema()->getInputFileFields();
        $method->appendCode("
            \$data = \$this->resolveFormInputs(\$request->all());
        ");
        $method->nl();

        foreach($inputFiles as $field) {;
            $method->appendCode($this->getUploadCode($field), "upload");
            $method->nl();
        }

        $method->appendCode("
            // Insert data
            \${$data->model_varname} = \$this->{$data->model->varname}->create(\$data);
            if (!\${$data->model_varname}) {
                \$message = 'Something went wrong when create {$data->label}';
                return back()->with('danger', \$message);
            }

            \$message = '{$data->label} has been created!';
            return redirect()->route('{$data->route->page_list}')->with('info', \$message);
        ");
    }

    protected function getUploadCode(Field $field)
    {
        $col = $field->getColumnName();
        $varName = camel_case($col);
        $path = $field->getUploadPath();
        $disk = $field->getUploadDisk();

        return "
            // Uploading {$col}
            \${$varName} = \$request->file('{$col}');
            if (\${$varName}) {
                \$filename = \${$varName}->getClientOriginalName();
                \$path = '{$path}';
                \$data['{$col}'] = \${$varName}->storeAs(\$path, \$filename, '{$disk}');
            }
        ";
    }

    protected function setMethodFormEdit(MethodGenerator $method)
    {
        $fieldsHasRelation = $this->getInputableFieldsHasRelation();
        $data = $this->getTableData();
        $method->addArgument('request', static::CLASS_REQUEST);
        $method->addArgument($data->primary_varname);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Display form edit {$data->model_varname}");
            $docblock->addParam('request', static::CLASS_REQUEST);
            $docblock->addParam($data->primary_varname, 'string');
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $initModelCode = $this->getInitModelCode();
        $method->appendCode($initModelCode);
        $method->nl();
        $view = $data->view->form_edit;
        $method->appendCode("\$data['title'] = 'Form Create {$data->label}';");
        $method->appendCode("\$data['{$data->model_varname}'] = \$this->resolveFormData(\${$data->model_varname}->toArray());");
        foreach($fieldsHasRelation as $field) {
            $relation = $field->getRelation();
            $varName = $relation['var_name'];
            $methodName = 'get'.ucfirst(camel_case($varName));
            $method->appendCode("\$data['{$varName}'] = \$this->{$methodName}();");
        }
        $method->nl();
        $method->appendCode("return view('{$view}', \$data);");
    }

    protected function setMethodPostEdit(MethodGenerator $method)
    {
        $data = $this->getTableData();
        $method->addArgument('request', $data->request->class_update_with_namespace);
        $method->addArgument($data->primary_varname);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Update specified {$data->model_varname}");
            $docblock->addParam('request', $data->request->class_update_with_namespace);
            $docblock->addParam($data->primary_varname, 'string');
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $initModelCode = $this->getInitModelCode();

        $inputFiles = $this->getTableSchema()->getInputFileFields();
        $method->appendCode($initModelCode);
        $method->nl();
        $method->appendCode("
            \$data = \$this->resolveFormInputs(\$request->all());
        ");
        $method->nl();
        foreach($inputFiles as $field) {
            $col = $field->getColumnName();
            $varName = camel_case($col);
            $path = $field->getUploadPath();
            $disk = $field->getUploadDisk();
            $method->appendCode("
                // Uploading {$col}
                \${$varName} = \$request->file('{$col}');
                if (\${$varName}) {
                    \$filename = \${$varName}->getClientOriginalName();
                    \$path = '{$path}';
                    \$data['{$col}'] = \${$varName}->storeAs(\$path, \$filename, '{$disk}');
                }
            ");
            $method->nl();
        }
        $method->appendCode("
            // Update data
            \${$data->model_varname}->fill(\$data);
            \$updated = \${$data->model_varname}->save();
            if (!\$updated) {
                \$message = 'Something went wrong when update {$data->label}';
                return back()->with('danger', \$message);
            }

            \$message = '{$data->label} has been updated!';
            return redirect()->route('{$data->route->page_list}')->with('info', \$message);
        ");
    }

    protected function setMethodDelete(MethodGenerator $method)
    {
        $data = $this->getTableData();
        $method->addArgument('request', static::CLASS_REQUEST);
        $method->addArgument($data->primary_varname);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Delete specified {$data->model_varname}");
            $docblock->addParam('request', static::CLASS_REQUEST);
            $docblock->addParam($data->primary_varname, 'string');
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        $initModelCode = $this->getInitModelCode();
        $method->appendCode($initModelCode);
        $method->nl();
        $method->appendCode("
            // Delete data
            \$deleted = \${$data->model_varname}->delete();
            if (!\$deleted) {
                \$message = 'Something went wrong when delete {$data->label}';
                return back()->with('danger', \$message);
            }

            \$message = '{$data->label} has been deleted!';
            return redirect()->route('{$data->route->page_list}')->with('info', \$message);
        ");
    }

    protected function setMethodFindOrFail(MethodGenerator $method)
    {
        $data = $this->getTableData();
        $joins = [];
        $inputableFieldsHasRelation = $this->getInputableFieldsHasRelation();
        foreach($inputableFieldsHasRelation as $field) {
            $relation = $field->getRelation();
            $relatedTable = $this->getTableSchema()->getRootSchema()->getTable($relation['table']);
            $tableVarname = $relatedTable->getSingularName();
            $colLabel = $relation['col_label'];
            $colLabelAlias = $relation['col_alias'];

            $joins[] = [
                'table' => $relation['table'],
                'type' => 'inner',
                'key_from' => $relation['key_from'],
                'key_to' => $relation['key_to'],
                'selects' => [
                    $colLabelAlias? $colLabel.' as '.$colLabelAlias : $colLabel
                ],
            ];
        }

        $method->setVisibility(MethodGenerator::VISIBILITY_PROTECTED);
        $method->addArgument($data->primary_varname);
        $method->setDocblock(function($docblock) use ($data) {
            $docblock->addText("Find {$data->model_varname} by '{$data->primary_key}' or display 404 if not exists");
            $docblock->setReturn(static::CLASS_RESPONSE);
        });

        if (!empty($joins)) {
            $method->appendCode("
                \${$data->model_varname} = \$this->{$data->model->varname}->find(\${$data->primary_varname});
            ");
        } else {
            $method->appendCode("
                \${$data->model_varname} = \$this->{$data->model->varname}->find(\${$data->primary_varname});
            ");
        }

        $method->appendCode("
            if (!\${$data->model_varname}) {
                return abort(404, '{$data->label} not found');
            }

            return \${$data->model_varname};
        ");
    }

    protected function setMethodResolveFormInputs(MethodGenerator $method)
    {
        $method->setVisibility('protected');
        $resolveableFields = array_filter($this->getTableSchema()->getFields(), function($field) {
            return $field->hasInput() AND !empty($field->getInputResolver());
        });

        $method->setDocblock(function($docblock) {
            $docblock->addText('Resolve form inputs into storable data.');
            $docblock->addParam('inputs', 'array');
            $docblock->setReturn('array');
        });

        $method->addArgument('inputs', 'array');
        foreach($resolveableFields as $field) {
            $name = $field->getColumnName();
            $inputResolver = (new Stub($field->getInputResolver()))->render([
                'value' => "\$inputs['{$name}']"
            ]);
            $method->appendCode("
                // Resolve input {$name}
                \$inputs['{$name}'] = {$inputResolver};
            ");
            $method->nl();
        }
        $method->appendCode("return \$inputs;");
    }

    protected function setMethodResolveFormData(MethodGenerator $method)
    {
        $method->setVisibility('protected');
        $resolveableFields = array_filter($this->getTableSchema()->getFields(), function($field) {
            return $field->hasInput() AND !empty($field->getDataResolver());
        });

        $method->setDocblock(function($docblock) {
            $docblock->addText('Resolve data (form database) into form values.');
            $docblock->addParam('data', 'array');
            $docblock->setReturn('array');
        });

        $method->addArgument('data', 'array');
        foreach($resolveableFields as $field) {
            $name = $field->getColumnName();
            $inputResolver = (new Stub($field->getDataResolver()))->render([
                'value' => "\$data['{$name}']"
            ]);
            $method->appendCode("
                // Resolve input {$name}
                \$data['{$name}'] = {$inputResolver};
            ");
            $method->nl();
        }
        $method->appendCode("return \$data;");
    }

    protected function addMethodsFormOptions()
    {
        $fieldsHasRelation = $this->getInputableFieldsHasRelation();
        foreach($fieldsHasRelation as $field) {
            $relation = $field->getRelation();
            $varName = $relation['var_name'];
            $methodName = 'get'.ucfirst(camel_case($varName));
            $colValue = $relation['col_value'];
            $colLabel = $relation['col_label'];
            $relatedTable = $this->getTableSchema()->getRootSchema()->getTable($relation['table']);
            $relatedTableName = $relatedTable->getName();
            $model = camel_case($relatedTable->getSingularName());
            $listVarname = camel_case($relatedTableName);

            $method = $this->addMethod($methodName);
            $method->setVisibility('protected');
            $method->setDocblock(function($docblock) use ($varName) {
                $docblock->addText('Get '.$varName);
                $docblock->setReturn('array');
            });
            $method->appendCode("
                return \$this->{$model}
                ->select(['{$colValue} as value', '{$colLabel} as label'])
                ->get()
                ->toArray();
            ");
        }
    }

    protected function getInitModelCode()
    {
        $data = $this->getTableData();
        return "\${$data->model_varname} = \$this->findOrFail(\${$data->primary_varname});";
    }

    protected function getRequiredModels()
    {
        $models = [];
        $varName = camel_case($this->getTableSchema()->getSingularName());
        $models[$varName] = $this->getTableSchema()->getModelClass();
        $relations = $this->getTableSchema()->getRelations();
        foreach($relations as $relation) {
            $table = $relation['table'];
            $tableSchema = $this->getTableSchema()->getRootSchema()->getTable($table);
            $modelClass = $tableSchema->getModelClass();
            $varName = camel_case($tableSchema->getSingularName());
            if (!in_array($modelClass, $models)) {
                $models[$varName] = $modelClass;
            }
        }

        return $models;
    }

    protected function getInputableFieldsHasRelation()
    {
        return array_filter($this->getTableSchema()->getFields(), function($field) {
            return !empty($field->getRelation()) AND $field->hasInput();
        });
    }

}
