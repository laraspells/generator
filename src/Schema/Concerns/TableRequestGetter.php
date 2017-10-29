<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableRequestGetter
{

    /**
     * Get request namespace
     *
     * @return string
     */
    public function getRequestNamespace()
    {
        return $this->get('request.namespace');
    }

    /**
     * Get request filepath
     *
     * @param string $request
     * @return string
     */
    public function getRequestPath($request = null)
    {
        $requestPath = $this->get('request.path');
        return $request? $requestPath.'/'.$request.'.php' : $requestPath;
    }

    /**
     * Get request class name (with namespace)
     *
     * @param string $request
     * @return string
     */
    public function getRequestClass($class)
    {
        return $this->getRequestNamespace()."\\".$class;
    }

    /**
     * Get create request filepath
     *
     * @return string
     */
    public function getCreateRequestPath()
    {
        return $this->getRequestPath($this->getCreateRequestClass(false));
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
        $createRequest = $this->get('request.create_class') ?: 'Create'.ucfirst(camel_case(str_singular($table))).'Request';
        return $namespace? $this->getRequestClass($createRequest) : $createRequest;
    }

    /**
     * Get update request filepath
     *
     * @return string
     */
    public function getUpdateRequestPath()
    {
        return $this->getRequestPath($this->getUpdateRequestClass(false));
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
        $updateRequest = $this->get('request.update_class') ?: 'Update'.ucfirst(camel_case(str_singular($table))).'Request';
        return $namespace? $this->getRequestClass($updateRequest) : $updateRequest;
    }

}
