<?php

namespace LaraSpells\Generator\Schema;

class Table extends AbstractSchema
{

    use Concerns\TableViewGetter;
    use Concerns\TableControllerGetter;
    use Concerns\TableModelGetter;
    use Concerns\TableRequestGetter;
    use Concerns\TableRouteGetter;

    protected $tableName;
    protected $rootSchema;
    protected $fields = [];

    public function __construct($tableName, $schema)
    {
        $this->tableName = $tableName;
        parent::__construct($schema);
        $this->initFields();
    }

    /**
     * Get root schema
     *
     * @return LaraSpells\Generator\Schema\Schema
     */
    public function getRootSchema()
    {
        return $this->rootSchema;
    }

    /**
     * Set root schema
     *
     * @param LaraSpells\Generator\Schema\Schema $rootSchema
     */
    public function setRootSchema(Schema $rootSchema)
    {
        $this->rootSchema = $rootSchema;
    }

    /**
     * Determine if table has it's own CRUD or not
     *
     * @return bool
     */
    public function hasCrud()
    {
        return $this->get('crud') === true;
    }

    /**
     * Get crud fields
     *
     * @return array of LaraSpells\Generator\Schema\Field
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get field schema
     *
     * @return LaraSpells\Generator\Schema\Field
     */
    public function getField($colname)
    {
        return isset($this->fields[$colname])? $this->fields[$colname] : null;
    }

    /**
     * Add field schema
     */
    public function addField($colname, Field $field)
    {
        $field->setTable($this);
        $this->fields[$colname] = $field;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function getName()
    {
        return $this->tableName;
    }

    public function getSingularName()
    {
        return $this->get('singular');
    }

    public function getPluralName()
    {
        return $this->get('plural');
    }

    /**
     * Get primary field
     *
     * @return LaraSpells\Generator\Schema\Field|null
     */
    public function getPrimaryField()
    {
        return array_first($this->fields, function($field) {
            return $field->isPrimary();
        });
    }

    /**
     * Get primary column name
     *
     * @return string|null
     */
    public function getPrimaryColumn()
    {
        $primaryField = $this->getPrimaryField();

        return $primaryField? $primaryField->getColumnName() : null;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->get('label');
    }

    /**
     * Get migration file path
     *
     * @return string
     */
    public function getMigrationPath()
    {
        return $this->getRootSchema()->getMigrationPath($this->getName());
    }

    /**
     * Get migration class name
     *
     * @return string
     */
    public function getMigrationClass()
    {
        return $this->getRootSchema()->getMigrationClass($this->getName());
    }

    /**
     * Determine the crud has timestamps field for migration
     *
     * @return string
     */
    public function usingTimestamps()
    {
        return $this->get('timestamps') === true;
    }

    /**
     * Determine the crud is using soft delete
     *
     * @return string
     */
    public function usingSoftDelete()
    {
        return $this->get('soft_delete') === true;
    }

    /**
     * Get menu icon
     *
     * @return string
     */
    public function getMenuIcon()
    {
        return $this->get('icon');
    }

    /**
     * Get hidden fields
     *
     * @return array
     */
    public function getHiddenFields()
    {
        return array_filter($this->getFields(), function($field) {
            return $field->isHidden();
        });
    }

    /**
     * Get fields that need to input file
     *
     * @return array
     */
    public function getInputFileFields()
    {
        return array_filter($this->getFields(), function($field) {
            return $field->isInputFile();
        });
    }

    /**
     * Get searchable fields
     *
     * @return array
     */
    public function getSearchableFields()
    {
        return array_filter($this->getFields(), function($field) {
            return $field->isSearchable();
        });
    }

    /**
     * Get sortable fields
     *
     * @return array
     */
    public function getSortableFields()
    {
        return array_filter($this->getFields(), function($field) {
            return $field->isSortable();
        });
    }

    /**
     * Get inputable fields
     *
     * @return array
     */
    public function getInputableFields()
    {
        return array_filter($this->getFields(), function($field) {
            return $field->hasInput();
        });
    }

    /**
     * Get fillable columns
     *
     * @return array
     */
    public function getFillableColumns()
    {
        return array_values(array_map(function($field) {
            return $field->getColumnName();
        }, $this->getInputableFields()));
    }

    public function getRelations()
    {
        return $this->get('relations');
    }

    protected function validateAndResolveSchema(array $schema)
    {
        $this->validateSchema($schema);
        return $this->resolveSchema($schema);
    }

    protected function validateSchema(array $schema)
    {
        if (!isset($schema['table'])) {
            throw new \InvalidArgumentException("Crud schema must have 'table' key");
        }

        if (!isset($schema['fields'])) {
            throw new \InvalidArgumentException("Schema crud '{$table}' must have 'fields' key");
        }

        if (empty($schema['fields'])) {
            throw new \InvalidArgumentException("Schema crud '{$table}' must have at least 1 field");
        }
    }

    protected function resolveSchema(array $schema)
    {
        $fields = $schema['fields'];
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

        if (!isset($schema['label'])) {
            $singularName = isset($schema['singular']) ? $schema['singular'] : str_singular($schema['table']);
            $schema['label'] = ucwords(snake_case(camel_case($singularName), ' '));
        }

        $schema['fields'] = $fields;

        if (!isset($schema['timestamps'])) {
            $schema['timestamps'] = true;
        }

        return $schema;
    }

    protected function initFields()
    {
        $fields = $this->get('fields');
        foreach($fields as $colname => $schema) {
            $schema = new Field($colname, $schema);
            $this->addField($colname, $schema);
        }
    }
}
