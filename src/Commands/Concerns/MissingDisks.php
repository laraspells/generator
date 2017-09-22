<?php

namespace LaraSpells\Generator\Commands\Concerns;

use LaraSpells\Generator\Generators\CodeGenerator;

trait MissingDisks
{
    protected $missingDisks = [];

    /**
     * Add missing disk
     *
     * @param  string $disk
     * @param  string $driver
     * @param  array $configs
     * @param  void
     */
    public function addMissingDisk($disk, $driver, array $configs, $update = false)
    {
        if (isset($this->missingDisks[$disk]) AND !$update) {
            return false;
        }

        $this->missingDisks[$disk] = array_merge($configs, [
            'driver' => $driver
        ]);
    }

    /**
     * Get registered missing disks
     *
     * @return rray
     */
    public function getMissingDisks()
    {
        return $this->missingDisks;
    }

    /**
     * Get missing filesystem disks
     *
     * @return array
     */
    protected function getCrudMissingDisks()
    {
        $availableDisks = array_keys(config('filesystems.disks'));
        $missingDisks = [];
        $tables = $this->getTablesToGenerate();
        foreach($tables as $table) {
            foreach($table->getFields() as $field) {
                if (!$field->isInputFile()) continue;
                $disk = $field->getUploadDisk();
                if (!in_array($disk, $availableDisks)) {
                    if(!isset($missingDisks[$disk])) {
                        $diskNamePlural = snake_case(str_plural($disk), '-');
                        $missingDisks[$disk] = [
                            'driver' => 'local',
                            'root' => "eval(\"public_path('{$diskNamePlural}')\")",
                            'url' => "eval(\"env('APP_URL').'/{$diskNamePlural}'\")",
                            'visibility' => 'public'
                        ];
                    }
                }
            }
        }

        return $missingDisks;
    }

    /**
     * Get missing disks suggestion
     *
     * @return string
     */
    public function getMissingDisksSuggestion()
    {
        // Suggestion missing disks
        $missingDisks = array_merge($this->getCrudMissingDisks(), $this->getMissingDisks());
        $providers = config('app.providers');
        $providerClass = $this->getSchema()->getServiceProviderClass();
        $generatedMigrations = $this->generatedMigrations;
        $suggestion = null;
        if (count($missingDisks)) {
            $disks = [];
            $code = new CodeGenerator;
            foreach($missingDisks as $disk => $configs) {
                $disks[] = "'{$disk}' => ".$code->phpify($configs, true);
            }
            $code->addCode("
                // Find this section
                'disks' => [
                    ...
                    // Add codes below
                    ".implode(",".PHP_EOL, $disks)."
                    // To this
                ]
            ");
            $suggestion = "Add ".(count($disks) > 1? 'disks' : 'disk')." configuration to your 'config/filesystems.php':".PHP_EOL.$code->generateCode();
        }

        return $suggestion;
    }

}
