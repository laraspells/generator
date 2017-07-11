<?php

namespace LaraSpell\Commands\Concerns;

use LaraSpell\Schema\Table;

trait MigrationUtils
{

    protected function getExistingMigrationFile(Table $table)
    {
        $table = $table->getName();
        $migrations = glob(base_path('database/migrations/*.php'));
        foreach($migrations as $migration) {
            $content = file_get_contents($migration);
            if (str_contains($content, "Schema::create('{$table}'")) {
                return ltrim(str_replace(base_path(), '', $migration), '/');
            }
        }
        return null;
    }

}
