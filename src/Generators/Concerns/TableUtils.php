<?php

namespace LaraSpell\Generators\Concerns;

use LaraSpell\Schema\Table;

trait TableUtils
{

    protected $tableData;
    protected $tableSchema;

    /**
     * Set table schema.
     *
     * @param  LaraSpell\Schema\Table $tableSchema
     * @return void
     */
    public function setTableSchema(Table $tableSchema)
    {
        $this->tableSchema = $tableSchema;
        return $this;
    }

    /**
     * Get table schema.
     *
     * @return null|LaraSpell\Schema\Table
     */
    public function getTableSchema()
    {
        return $this->tableSchema;
    }

    /**
     * Get table common data.
     *
     * @return stdClass
     */
    public function getTableData($key = null)
    {
        if (!$this->tableData) {
            $schema = $this->getTableSchema();
            $data = [
                'label' => $schema->getLabel(),
                'table_name' => $schema->getName(),
                'singular_name' => $schema->getSingularName(),
                'model_varname' => camel_case($schema->getSingularName()),
                'primary_key' => $schema->getPrimaryColumn(),
                'primary_varname' => camel_case($schema->getPrimaryColumn()),
                'controller' => [
                    'class' => $schema->getControllerClass(false),
                    'class_with_namespace' => $schema->getControllerClass(true),
                    'file' => $schema->getControllerPath(),
                ],
                'request' => [
                    'class_create' => $schema->getCreateRequestClass(false),
                    'class_create_with_namespace' => $schema->getCreateRequestClass(true),
                    'class_update' => $schema->getUpdateRequestClass(false),
                    'class_update_with_namespace' => $schema->getUpdateRequestClass(true),
                ],
                'model' => [
                    'namespace' => $schema->getRootSchema()->getModelNamespace(),
                    'class' => $schema->getModelClass(false),
                    'class_with_namespace' => $schema->getModelClass(true),
                    'file' => $schema->getModelPath(),
                ],
                'controller' => [
                    'namespace' => $schema->getRootSchema()->getControllerNamespace(),
                    'class' => $schema->getControllerClass(false),
                    'class_with_namespace' => $schema->getControllerClass(true),
                    'file' => $schema->getControllerPath(),
                ],
                'repository' => [
                    'class' => $schema->getRepositoryClass(false),
                    'class_with_namespace' => $schema->getRepositoryClass(true),
                    'interface' => $schema->getRepositoryInterface(false),
                    'interface_with_namespace' => $schema->getRepositoryInterface(true),
                    'file_class' => $schema->getRepositoryClassPath(),
                    'file_interface' => $schema->getRepositoryInterfacePath(),
                    'varname' => camel_case($schema->getSingularName()),
                ],
                'view' => [
                    'page_list' => $schema->getViewListName(),
                    'page_detail' => $schema->getViewDetailName(),
                    'form_create' => $schema->getViewCreateName(),
                    'form_edit' => $schema->getViewEditName(),
                ],
                'route' => [
                    'page_list' => $schema->getRouteListName(),
                    'page_detail' => $schema->getRouteDetailName(),
                    'form_create' => $schema->getRouteCreateName(),
                    'post_create' => $schema->getRoutePostCreateName(),
                    'form_edit' => $schema->getRouteEditName(),
                    'post_edit' => $schema->getRoutePostEditName(),
                    'delete' => $schema->getRouteDeleteName()
                ],
            ];

            $data = json_decode(json_encode($data));
        }

        return $data;
    }

}
