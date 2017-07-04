<?php

namespace LaraSpell;

use LaraSpell\Exceptions\InvalidSchemaException;
use LaraSpell\Schema\Field;

class SchemaResolver implements SchemaResolverInterface
{

    protected $availableFieldTypes = [
       "char",
       "string",
       "text",
       "medium-text",
       "long-text",
       "integer",
       "tiny-integer",
       "small-integer",
       "medium-integer",
       "big-integer",
       "unsigned-integer",
       "unsigned-tiny-integer",
       "unsigned-small-integer",
       "unsigned-medium-integer",
       "unsigned-big-integer",
       "float",
       "double",
       "decimal",
       "boolean",
       "enum",
       "json",
       "jsonb",
       "date",
       "date-time",
       "date-time-tz",
       "time",
       "time-tz",
       "timestamp",
       "timestamp-tz",
       "binary",
       "uuid",
       "ip-address"
    ];

    protected $availableInputTypes = [
        'text',
        'textarea',
        'file',
        'image',
        'number',
        'email',
        'select',
        'select-multiple',
        'checkbox',
        'radio',
    ];

    public function resolve(array $schema)
    {
        return $this->resolveSchema($schema);
    }

    /**
     * Resolve root schema
     *
     * @param  array $schema
     * @return array
     */
    protected function resolveSchema($schema)
    {
        $this->validateRootSchema($schema);

        data_fill($schema, 'controller.path', 'app/Http/Controllers');
        data_fill($schema, 'controller.namespace', 'App\Http\Controllers');

        data_fill($schema, 'request.path', 'app/Http/Requests');
        data_fill($schema, 'request.namespace', 'App\Http\Requests');

        data_fill($schema, 'model.path', 'app');
        data_fill($schema, 'model.namespace', 'App');

        data_fill($schema, 'repository.path', 'app/Repositories');
        data_fill($schema, 'repository.namespace', 'App\Repositories');

        data_fill($schema, 'view.path', 'resources/views');
        data_fill($schema, 'view.namespace', '');

        data_fill($schema, 'route.file', 'routes/web.php');
        data_fill($schema, 'route.name', 'admin::');
        data_fill($schema, 'route.prefix', 'admin');

        data_fill($schema, 'migration.path', 'database/migrations');

        data_fill($schema, 'config_file', 'admin');

        // Resolve tables
        $tables = array_get($schema, 'tables') ?: [];
        foreach($tables as $table => $tableSchema) {
            $schema['tables'][$table] = $this->resolveTableSchema($table, $tableSchema);
        }

        $schema['tables'] = $this->resolveTablesRelations($schema['tables']);

        return $schema;
    }

    /**
     * Resolve root schema
     *
     * @param  array $tableSchema
     * @return array
     */
    protected function resolveTableSchema($tableName, array $tableSchema)
    {
        $this->validateTableSchema($tableName, $tableSchema);

        // Resolve singular and plural name
        $tableNameIsSingular = false;
        if (isset($tableSchema['singular'])) {
            $tableNameIsSingular = $tableName == $tableSchema['singular'];
        } else {
            data_fill($tableSchema, 'singular', str_singular($tableName));
        }

        if (!isset($tableSchema['plural'])) {
            data_fill($tableSchema, 'plural', $tableNameIsSingular? str_plural($tableName) : $tableName);
        }

        $tableSchema = array_merge([
            'timestamps' => true,
            'label' => ucwords(snake_case(camel_case(str_singular($tableName)), ' '))
        ], $tableSchema);

        // Add field id as primary key if PK is not exists
        $fields = $tableSchema['fields'];
        $hasPrimary = (bool) array_first($fields, function($field) {
            return isset($field['index']) AND $field['index'] == Field::INDEX_PRIMARY;
        });
        if (!$hasPrimary) {
            $fields = array_merge([
                'id' => [
                    "type" => "integer",
                    "index" => Field::INDEX_PRIMARY,
                    "auto_increment" => true
                ]
            ], $fields);
        }
        $tableSchema['fields'] = $fields;

        // Resolve fields
        foreach($tableSchema['fields'] as $colName => $fieldSchema) {
            $tableSchema['fields'][$colName] = $this->resolveFieldSchema($colName, $fieldSchema, $tableName);
        }

        return $tableSchema;
    }

    /**
     * Resolve root schema
     *
     * @param  array $fieldSchema
     * @return array
     */
    protected function resolveFieldSchema($colName, array $fieldSchema, $tableName)
    {
        $this->validateFieldSchema($colName, $fieldSchema, $tableName);

        list($type, $typeParams) = $this->parseType(array_get($fieldSchema, 'type'));

        // Set searchable
        $searchableTypes = ['varchar', 'string', 'text', 'enum'];
        if (in_array($type, $searchableTypes) AND !isset($fieldSchema['searchable']) AND $colName != 'password') {
            $fieldSchema['searchable'] = true;
        }

        // Set sortable
        $sortableTypes = ['varchar', 'string', 'text', 'enum', 'date', 'datetime', 'timestamp'];
        if (in_array($type, $sortableTypes) AND !isset($fieldSchema['sortable'])) {
            $fieldSchema['sortable'] = true;
        }

        // Resolve input type
        if (isset($fieldSchema['input']) AND is_string($fieldSchema['input'])) {
            $fieldSchema['input'] = [
                'type' => $fieldSchema['input']
            ];
        }

        // Resolve rules
        if (isset($fieldSchema['rules'])) {
            $rules = $fieldSchema['rules'];
            if (is_string($rules)) {
                $fieldSchema['rules'] = explode("|", $rules);
            }
        } else {
            $fieldSchema['rules'] = [];
        }

        // Add input param if field has required rule
        if (in_array("required", $fieldSchema['rules']) AND !isset($fieldSchema['input']['required'])) {
            $fieldSchema['input']['required'] = true;
        }

        // Set input max length
        if (
            isset($fieldSchema['length'])
            AND in_array($fieldSchema['input']['type'], ['text', 'textarea'])
            AND !isset($fieldSchema['input']['maxlength'])
        ) {
            $fieldSchema['input']['maxlength'] = $fieldSchema['length'];
        }

        // Resolve field by type
        $fieldTypeResolver = 'resolveFieldType'.ucfirst(camel_case($fieldSchema['type']));
        if (method_exists($this, $fieldTypeResolver)) {
            $fieldSchema = $this->{$fieldTypeResolver}($colName, $fieldSchema, $tableName);
        }

        // Resolve field by input type
        if (isset($fieldSchema['input'])) {
            $fieldSchema['input']['view'] = $fieldSchema['input']['type'];

            $inputType = $fieldSchema['input']['type'];
            $inputTypeResolver = 'resolveFieldInput'.ucfirst(camel_case($inputType));
            if (method_exists($this, $inputTypeResolver)) {
                $fieldSchema = $this->{$inputTypeResolver}($colName, $fieldSchema, $tableName);
            }
        }

        // Resolve field display (in table)
        if (!isset($fieldSchema['table_code']) AND isset($fieldSchema['display']) AND preg_match("/^[a-z_-]+$/i", $fieldSchema['display'])) {
            $tableCodeResolver = 'getTableDisplay'.ucfirst(camel_case($fieldSchema['display']));
            if (method_exists($this, $tableCodeResolver)) {
                $fieldSchema['table_code'] = $this->{$tableCodeResolver}($fieldSchema);
            }
        } else {
            data_fill($fieldSchema, 'table_code', '{{ ${? varname ?}[\'{? column ?}\'] }}');
        }

        // Get read field code
        $fieldSchema['read_code'] = $this->getReadFieldCode($fieldSchema);

        if (true === array_get($fieldSchema, 'input.multiple')) {
            $fieldSchema = $this->resolveInputMultiple($colName, $fieldSchema, $tableName);
        }

        return $fieldSchema;
    }

    protected function resolveFieldInputFile($colName, $fieldSchema, $tableName)
    {
        data_fill($fieldSchema, 'display', 'link');
        return $fieldSchema;
    }

    protected function resolveFieldInputImage($colName, $fieldSchema, $tableName)
    {
        data_fill($fieldSchema, 'display', 'image-link');
        return $fieldSchema;
    }

    protected function resolveFieldInputSelect($colName, $fieldSchema, $tableName)
    {
        return $this->resolveOptionableField($colName, $fieldSchema, $tableName);
    }

    protected function resolveFieldInputRadio($colName, $fieldSchema, $tableName)
    {
        return $this->resolveOptionableField($colName, $fieldSchema, $tableName);
    }

    protected function resolveFieldInputCheckbox($colName, $fieldSchema, $tableName)
    {
        data_fill($fieldSchema, 'input.multiple', true);
        return $this->resolveOptionableField($colName, $fieldSchema, $tableName);
    }

    protected function resolveFieldInputSelectMultiple($colName, $fieldSchema, $tableName)
    {
        data_fill($fieldSchema, 'input.multiple', true);
        return $this->resolveOptionableField($colName, $fieldSchema, $tableName);
    }

    protected function resolveOptionableField($colName, $fieldSchema, $tableName)
    {
        $fieldSchema = $this->resolveRelationFromOptionableField($colName, $fieldSchema, $tableName);
        if (is_array($fieldSchema['input']['options'])) {
            $options = [];
            foreach($fieldSchema['input']['options'] as $value => $label) {
                $options[] = ['value' => $value, 'label' => $label];
            }
            $fieldSchema['input']['options'] = $options;
        }
        return $fieldSchema;
    }

    protected function resolveRelationFromOptionableField($colName, $fieldSchema, $tableName)
    {
        if (!isset($fieldSchema['input']['options'])) {
            return $fieldSchema;
        }

        $optionSetting = $fieldSchema['input']['options'];
        $optionKeys = ['table', 'value', 'label'];
        $needRelation = true;
        foreach($optionKeys as $key) {
            if (!isset($optionSetting[$key])) {
                $needRelation = false;
                break;
            }
        }

        if (!$needRelation) {
            return $fieldSchema;
        }

        $tableSingular = str_singular($optionSetting['table']);
        $optionsVarname = $tableSingular.'_options';
        $columnAlias = null;
        if (!starts_with($optionSetting['label'], $tableSingular)) {
            $columnAlias = $tableSingular.'_'.$optionSetting['label'];
        }

        $fieldSchema['relation'] = [
            'table' => $optionSetting['table'],
            'type' => (true === array_get($optionSetting, 'multiple'))? 'has-many' : 'has-one',
            'key_from' => $colName,
            'key_to' => $optionSetting['value'],
            'col_value' => $optionSetting['value'],
            'col_label' => $optionSetting['label'],
            'col_alias' => $columnAlias,
            'var_name' => $optionsVarname,
        ];

        $fieldSchema['input']['options'] = "eval(\"\${$optionsVarname}\")";

        return $fieldSchema;
    }

    /**
     * Validate root schema
     *
     * @param  array $schema
     * @return void
     */
    protected function validateRootSchema(array $schema)
    {
        $this->assertHasKeys($schema, ['tables'], "Schema must have key '{{key}}'");
        $this->assertTrue(is_array($schema['tables']) AND !empty($schema['tables']), "Schema must have at least 1 table to generate");
    }

    /**
     * Validate table schema
     *
     * @param  array $tableSchema
     * @return void
     */
    protected function validateTableSchema($table, array $tableSchema)
    {
        $this->assertHasKeys($tableSchema, ['fields'], "Table '{$table}' must have key '{{key}}'");
        $this->assertTrue(is_array($tableSchema['fields']) AND !empty($tableSchema['fields']), "Schema crud '{$table}' must have at least 1 field");
    }

    /**
     * Validate field schema
     *
     * @param  array $fieldSchema
     * @return void
     */
    protected function validateFieldSchema($column, array $fieldSchema, $tableName)
    {
        $suffix = "Found in table '{$tableName}', field '{$column}'.";
        $this->assertHasKeys($fieldSchema, ['type'], "Field must have key '{{key}}'. {$suffix}");

        // Validate type
        list($type, $typeParams) = $this->parseType($fieldSchema['type']);
        $this->assertTrue(is_string($type), "Type must be a string. {$suffix}");
        $this->assertTrue(in_array($type, $this->availableFieldTypes), "Type '{$type}' is not available. {$suffix}");

        // Validate input type
        $input = array_get($fieldSchema, 'input');
        if ($input) {
            $this->assertTrue(is_string($input) OR is_array($input), "Field must be array (with type) or string (input type). {$suffix}");
            if (is_array($input)) {
                $this->assertTrue(isset($input['type']), "Array field input must have 'type' key. {$suffix}");
            }

            $inputType = is_string($input)? $input : array_get($fieldSchema, 'input.type');
            if ($inputType AND !in_array($inputType, $this->availableInputTypes)) {
                return $this->showInvalidMessage("Input type '{$inputType}' is not available. {$suffix}");
            }
        }
    }

    /**
     * Resolve table relations
     *
     * @return array
     */
    protected function resolveTablesRelations(array $tables)
    {
        foreach($tables as $tableName => $tableSchema) {
            $relations = array_get($tableSchema, 'relations') ?: [];
            $fieldsHasRelation = array_filter($tableSchema['fields'], function($field) {
                return isset($field['relation']);
            });
            foreach($fieldsHasRelation as $colName => $field) {
                $relation = $field['relation'];
                $relatedTable = $relation['table'];
                if (!$this->tableHasRelation($tableSchema, $relatedTable, $relation['key_from'], $relation['key_to'])) {
                    if (!isset($tables[$relatedTable])) {
                        throw new InvalidSchemaException("Table '{$tableName}' has relation to table '{$relatedTable}', but table '{$relatedTable}' is not described in your schema.");
                    }
                    $relations[] = [
                        'table' => $relation['table'],
                        'type' => $relation['type'],
                        'key_from' => $relation['key_from'],
                        'key_to' => $relation['key_to']
                    ];
                }
            }

            // Find relation from another tables
            foreach($tables as $otherTableName => $otherTableSchema) {
                if ($otherTableName == $tableName) continue;
                $fieldsHasRelationToTable = array_filter($otherTableSchema['fields'], function($field) use ($tableName) {
                    return isset($field['relation']) AND $field['relation']['table'] == $tableName;
                });
                foreach($fieldsHasRelationToTable as $colName => $field) {
                    $relation = $field['relation'];
                    if (!$this->tableHasRelation($tableSchema, $otherTableName, $relation['key_to'], $relation['key_from'])) {
                        $relations[] = [
                            'table' => $otherTableName,
                            'type' => 'belongs-to',
                            'key_from' => $relation['key_to'],
                            'key_to' => $relation['key_from']
                        ];
                    }
                }
            }

            $tables[$tableName]['relations'] = $relations;
        }

        return $tables;
    }

    /**
     * Resolve field with input multpile
     *
     * @param  string $colName
     * @param  array $fieldSchema
     * @param  string $tableName
     * @return array
     */
    protected function resolveInputMultiple($colName, array $fieldSchema, $tableName)
    {
        data_fill($fieldSchema, 'input_resolver', "json_encode({? value ?})");
        data_fill($fieldSchema, 'data_resolver', "json_decode({? value ?})");
        return $fieldSchema;
    }

    /**
     * Check if table has given relation
     *
     * @param array $tableSchema
     * @param string $toTable
     * @param string $keyFrom
     * @param string $keyTo
     * @return bool
     */
    protected function tableHasRelation(array $tableSchema, $toTable, $keyFrom, $keyTo)
    {
        $relations = array_get($tableSchema, 'relations') ?: [];
        return null !== array_first($relations, function($relation) use ($toTable, $keyFrom, $keyTo) {
            return (
                $relation['table'] == $table
                AND $relation['key_from'] == $keyFrom
                AND $relation['key_to'] == $keyTo
            );
        });
    }

    protected function assertHasKeys(array $data, array $keys, $message)
    {
        foreach($keys as $key) {
            if (!array_has($data, $key)) {
                return $this->showInvalidMessage(str_replace('{{key}}', $key, $message));
            }
        }
    }

    protected function assertTrue($value, $message)
    {
        if (true !== $value) {
            $this->showInvalidMessage($message);
        }
    }

    protected function showInvalidMessage($message)
    {
        throw new InvalidSchemaException($message);
    }

    protected function parseType($type)
    {
        $exploded = explode(":", $type, 2);
        $type = $exploded[0];
        $params = [];
        if (isset($exploded[1])) {
            $params = array_map(function($value) {
                return trim($value);
            }, explode(",", $exploded[1]));
        }
        return [$type, $params];
    }

    protected function getReadFieldCode(array $fieldSchema)
    {
        $tableCode = $fieldSchema['table_code'];
        return '
            <tr>
                <td width="200" class="field-name"><strong>{? label ?}</strong></td>
                <td width="10" class="text-center">:</td>
                <td class="field-value">'.$tableCode.'</td>
            </tr>
        ';
    }

    protected function getTableDisplayLink(array $fieldSchema)
    {
        return '
            <a target="_blank" href="{{ Storage::disk(\'{? disk ?}\')->url(${? varname ?}[\'{? column ?}\']) }}">{{ ${? varname ?}[\'{? column ?}\'] }}</a>
        ';
    }

    protected function getTableDisplayImage(array $fieldSchema)
    {
        return '
            <img src="{{ Storage::disk(\'{? disk ?}\')->url(${? varname ?}[\'{? column ?}\']) }}" style="max-height:100px;width:auto;"/>
        ';
    }

    protected function getTableDisplayImageLink(array $fieldSchema)
    {
        return '
            <a target="_blank" href="{{ Storage::disk(\'{? disk ?}\')->url(${? varname ?}[\'{? column ?}\']) }}">
                <img src="{{ Storage::disk(\'{? disk ?}\')->url(${? varname ?}[\'{? column ?}\']) }}" style="max-height:100px;width:auto;"/>
            </a>
        ';
    }

    protected function getTableDisplayHtml(array $fieldSchema)
    {
        return '{!! ${? varname ?}[\'{? column ?}\'] !!}';
    }

}
