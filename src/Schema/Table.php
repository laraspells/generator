<?php

namespace LaraSpell\Schema;

class Table extends AbstractSchema
{

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
     * @return LaraSpell\Schema\Schema
     */
    public function getRootSchema()
    {
        return $this->rootSchema;
    }

    /**
     * Set root schema
     *
     * @param LaraSpell\Schema\Schema $rootSchema
     */
    public function setRootSchema(Schema $rootSchema)
    {
        $this->rootSchema = $rootSchema;
    }

    /**
     * Get crud fields
     *
     * @return array of LaraSpell\Schema\Field
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get field schema
     *
     * @return LaraSpell\Schema\Field
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
     * @return LaraSpell\Schema\Field|null
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
     * Get controller filepath
     *
     * @return string
     */
    public function getControllerPath()
    {
        return $this->getRootSchema()->getControllerPath($this->getControllerClass(false));
    }

    /**
     * Get controller class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getControllerClass($namespace = true)
    {
        $table = $this->getName();
        $controller = $this->get('controller') ?: ucfirst(camel_case($this->getSingularName())).'Controller';
        return $namespace? $this->getRootSchema()->getControllerClass($controller) : $controller;
    }

    /**
     * Get create request filepath
     *
     * @return string
     */
    public function getCreateRequestPath()
    {
        return $this->getRootSchema()->getRequestPath($this->getCreateRequestClass(false));
    }

    /**
     * Get create request class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getCreateRequestClass($namespace = true)
    {
        $table = $this->getName();
        $createRequest = $this->get('create_request') ?: 'Create'.ucfirst(camel_case(str_singular($table))).'Request';
        return $namespace? $this->getRootSchema()->getRequestClass($createRequest) : $createRequest;
    }

    /**
     * Get update request filepath
     *
     * @return string
     */
    public function getUpdateRequestPath()
    {
        return $this->getRootSchema()->getRequestPath($this->getUpdateRequestClass(false));
    }

    /**
     * Get update request class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getUpdateRequestClass($namespace = true)
    {
        $table = $this->getName();
        $updateRequest = $this->get('update_request') ?: 'Update'.ucfirst(camel_case(str_singular($table))).'Request';
        return $namespace? $this->getRootSchema()->getRequestClass($updateRequest) : $updateRequest;
    }

    /**
     * Get model file path
     *
     * @return string
     */
    public function getModelPath()
    {
        return $this->getRootSchema()->getModelPath($this->getModelClass(false));
    }

    /**
     * Get model class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getModelClass($namespace = true)
    {
        $table = $this->getName();
        $model = $this->get('model') ?: ucfirst(camel_case($this->getSingularName()));
        return $namespace? $this->getRootSchema()->getModelClass($model) : $model;
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
     * Get repository interface file path
     *
     * @return string
     */
    public function getRepositoryInterfacePath()
    {
        return $this->getRootSchema()->getRepositoryInterfacePath($this->getRepositoryInterface(false).'.php');
    }

    /**
     * Get repository interface file path
     *
     * @return string
     */
    public function getRepositoryClassPath()
    {
        return $this->getRootSchema()->getRepositoryClassPath($this->getRepositoryClass(false).'.php');
    }

    /**
     * Get repository interface name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getRepositoryInterface($namespace = true)
    {
        $interface = ucfirst(camel_case($this->getSingularName())).'Repository';
        return $namespace? $this->getRootSchema()->getRepositoryInterface($interface) : $interface;
    }

    /**
     * Get repository class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getRepositoryClass($namespace = true)
    {
        $class = ucfirst(camel_case($this->getSingularName())).'Repository';
        return $namespace? $this->getRootSchema()->getRepositoryClass($class) : $class;
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

    public function getViewListPath()
    {
        return $this->getViewPath('page-list');
    }

    public function getViewDetailPath()
    {
        return $this->getViewPath('page-detail');
    }

    public function getViewCreatePath()
    {
        return $this->getViewPath('form-create');
    }

    public function getViewEditPath()
    {
        return $this->getViewPath('form-edit');
    }

    public function getViewPath($view)
    {
        $table = str_singular($this->getName());
        return $this->getRootSchema()->getViewpath($table.'/'.$view);
    }

    public function getViewListName()
    {
        return $this->getViewName('page-list');
    }

    public function getViewDetailName()
    {
        return $this->getViewName('page-detail');
    }

    public function getViewCreateName()
    {
        return $this->getViewName('form-create');
    }

    public function getViewEditName()
    {
        return $this->getViewName('form-edit');
    }

    public function getViewName($view)
    {
        $dir = str_singular($this->getName());
        return $this->getRootSchema()->getView($dir.'.'.$view);
    }

    public function getRouteListName($namespace = true)
    {
        return $this->getRouteName('page-list', $namespace);
    }

    public function getRouteDetailName($namespace = true)
    {
        return $this->getRouteName('page-detail', $namespace);
    }

    public function getRouteCreateName($namespace = true)
    {
        return $this->getRouteName('form-create', $namespace);
    }

    public function getRoutePostCreateName($namespace = true)
    {
        return $this->getRouteName('post-create', $namespace);
    }

    public function getRouteEditName($namespace = true)
    {
        return $this->getRouteName('form-edit', $namespace);
    }

    public function getRoutePostEditName($namespace = true)
    {
        return $this->getRouteName('post-edit', $namespace);
    }

    public function getRouteDeleteName($namespace = true)
    {
        return $this->getRouteName('delete', $namespace);
    }

    public function getRouteName($action = '', $namespace = true)
    {
        return $this->getRootSchema()->getRouteName($this->getRoutePrefix().'.'.$action, $namespace);
    }

    public function getRoutePrefix()
    {
        return str_replace("_", "-", $this->getName());
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
            $table = $schema['table'];
            $schema['label'] = ucwords(snake_case(camel_case(str_singular($table)), ' '));
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
