<?php

namespace LaraSpells\Generator\Commands\Concerns;

trait MigrationUtils
{

    protected function getExistingMigrationFile($tableName)
    {
        $migrations = glob(base_path('database/migrations/*.php'));
        foreach($migrations as $migration) {
            $content = file_get_contents($migration);
            if (str_contains($content, "Schema::create('{$tableName}'")) {
                return ltrim(str_replace(base_path(), '', $migration), '/');
            }
        }
        return null;
    }

}
