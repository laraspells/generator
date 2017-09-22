<?php

namespace LaraSpells\Generator\Commands\Concerns;

use LaraSpells\Generator\Generators\CodeGenerator;

trait ConfigUtils
{

    protected $configs = [];

    public function getConfigs()
    {
        $schema = $this->getSchema();
        $configKey = $schema->getConfigKey();
        if (!$this->configs) {
            $this->configs = config($configKey) ?: [];
        }

        return $this->configs;
    }

    public function hasConfig($key)
    {
        return array_has($this->configs, $key);
    }

    public function setConfig($key, $value)
    {
        return array_set($this->configs, $key, $value);
    }

    public function getConfig($key)
    {
        return array_get($this->configs, $key);
    }

    public function addConfigMenu($route, $label, array $others = [], $update = false)
    {
        if (!isset($this->configs['menu'])) {
            $this->configs['menu'] = [];
        }

        $index = $this->getIndexConfigMenu($route);
        $exists = $index > -1;
        $dataMenu = array_merge([
            'label' => $label,
            'route' => $route,
        ], $others);

        if ($exists AND !$update) return false;

        if ($exists) {
            $this->configs['menu'][$index] = $dataMenu;
        } else {
            $this->configs['menu'][] = $dataMenu;
        }
    }

    public function persistConfigs()
    {
        $configs = $this->getConfigs();
        $filePath = 'config/'.$this->getSchema()->getConfigFile();
        $code = new CodeGenerator;
        $code->addCode("<?php\n\nreturn ".$code->phpify($configs, true).";");
        $code->nl();
        $this->writeFile($filePath, $code->generateCode());
    }

    protected function getIndexConfigMenu($route)
    {
        $menu = $this->getConfig('menu') ?: [];
        $index = -1;
        foreach($menu as $i => $m) {
            if ($m['route'] == $route) return $i;
        }
        return $index;
    }

}
