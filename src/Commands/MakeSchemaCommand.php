<?php

namespace LaraSpells\Generator\Commands;

use Illuminate\Console\Command;
use LaraSpells\Generator\Stub;
use Symfony\Component\Yaml\Yaml;

class MakeSchemaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        spell:make
        {file : Schema (yml) output file}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate laraspells schema file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('file');
        if (!ends_with($file, '.yml')) {
            $file .= '.yml';
        }
        $filename = pathinfo($file, PATHINFO_FILENAME);

        $filepath = base_path($file);
        if (file_exists($filepath)) {
            throw new \Exception("File '{$file}' already exists.", 1);
        }

        $stubPath = __DIR__.'/../stubs/schema.yml.stub';
        $stub = new Stub(file_get_contents($stubPath), [
            'filename' => $filename,
            'filename_camel' => ucfirst(camel_case($filename)),
        ]);

        file_put_contents($filepath, $stub->render());
        $this->info("Schema '{$file}' generated!");
    }
}
