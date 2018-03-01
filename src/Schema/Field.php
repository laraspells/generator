<?php

namespace LaraSpells\Generator\Schema;

class Field extends AbstractSchema
{

    const INDEX_PRIMARY = "primary";
    const INDEX_BASIC = "index";
    const INDEX_UNIQUE = "unique";

    protected $colname;
    protected $table;
    protected $fields = [];

    /**
     * __construct
     *
     * @return string
     */
    public function __construct($colname, array $schema)
    {
        $this->colname = $colname;
        parent::__construct($schema);
    }

    /**
     * Get table schema
     *
     * @return LaraSpells\Generator\Schema\Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set table schema
     *
     * @param LaraSpells\Generator\Schema\Table $table
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Get column name
     *
     * @return string
     */
    public function getColumnName()
    {
        return $this->colname;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->get('label') ?: $this->getColumnName();
    }

    /**
     * Get data type
     *
     * @return string
     */
    public function getType()
    {
        return $this->get('type');
    }

    /**
     * Get input type
     *
     * @return string
     */
    public function getInputType()
    {
        return $this->get('input.type');
    }

    /**
     * Get input view
     *
     * @return string
     */
    public function getInputView()
    {
        return 'partials.fields.'.$this->get('input.view');
    }

    /**
     * Get input parameters
     *
     * @return array
     */
    public function getInputParams()
    {
        $params['name'] = $this->getColumnName();
        $params['label'] = $this->getLabel();
        $params = array_merge($params, $this->get('input'));
        unset($params['type']);
        unset($params['view']);
        return $params;
    }

    /**
     * Get rules
     *
     * @return array
     */
    public function getRules()
    {
        return $this->get('rules') ?: [];
    }

    /**
     * Get column description (comment)
     *
     * @return bool
     */
    public function getDescription()
    {
        return $this->get('description');
    }

    /**
     * Get column default value
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->get('default');
    }

    /**
     * Get column length
     *
     * @return string
     */
    public function getLength()
    {
        return (int) $this->get('length');
    }

    /**
     * Get database index type
     *
     * @return string
     */
    public function getIndex()
    {
        return $this->get('index');
    }

    /**
     * Check wether this column is AUTO_INCREMENT
     *
     * @return bool
     */
    public function isAutoIncrement()
    {
        return $this->get('auto_increment') === true;
    }

    /**
     * Check wether this column is NULLABLE
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->get('nullable') === true;
    }

    /**
     * Check wether this column is primary key
     *
     * @return bool
     */
    public function isPrimary()
    {
        return $this->getIndex() == static::INDEX_PRIMARY;
    }

    /**
     * Check wether this column is required
     *
     * @return bool
     */
    public function isRequired()
    {
        return in_array("required", $this->getRules());
    }

    public function isHidden()
    {
        return $this->get('hidden') === true;
    }

    public function isInputFile()
    {
        return in_array($this->getInputType(), ['file', 'image']);
    }

    public function isSearchable()
    {
        return $this->get('searchable') === true;
    }

    public function isSortable()
    {
        return $this->get('sortable') === true;
    }

    public function hasInput()
    {
        return (bool) $this->getInputType();
    }

    public function getUploadPath()
    {
        return $this->get('upload_path') ?: $this->getTable()->getName().'/'.str_plural(snake_case($this->getColumnName(), '-'));
    }

    public function getUploadDisk()
    {
        return $this->get('upload_disk') ?: $this->getTable()->getRootSchema()->getUploadDisk();
    }

    public function getDisplay()
    {
        return $this->get('display');
    }

    public function getTableCode()
    {
        return $this->get('table_code');
    }

    public function getReadCode()
    {
        return $this->get('read_code');
    }

    public function getRelation()
    {
        return $this->get('relation');
    }

    public function getInputResolver()
    {
        return $this->get('input_resolver');
    }

    public function getDataResolver()
    {
        return $this->get('data_resolver');
    }

    protected function validateAndResolveSchema(array $schema)
    {
        $this->validateSchema($schema);
        return $this->resolveSchema($schema);
    }

    protected function validateSchema(array $schema)
    {
        $colname = $this->colname;
        $this->assertHasKeys($schema, [
            'type'
        ], "Field '{$colname}' must have key '{{key}}'");
    }

    protected function resolveSchema(array $schema)
    {
        $type = explode(':', array_get($schema, 'type'))[0];

        $searchableTypes = ['varchar', 'string', 'text', 'enum'];
        if (in_array($type, $searchableTypes) AND !isset($schema['searchable']) AND $this->colname != 'password') {
            $schema['searchable'] = true;
        }

        $sortableTypes = ['varchar', 'string', 'text', 'enum', 'date', 'datetime', 'timestamp'];
        if (in_array($type, $sortableTypes) AND !isset($schema['sortable'])) {
            $schema['sortable'] = true;
        }

        return $schema;
    }
}
