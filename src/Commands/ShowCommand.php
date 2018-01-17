<?php

namespace LaraSpells\Generator\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use LaraSpells\Generator\Exceptions\InvalidTemplateException;
use LaraSpells\Generator\Extension;
use LaraSpells\Generator\Generator;
use LaraSpells\Generator\Generators\CodeGenerator;
use LaraSpells\Generator\Generators\ControllerGenerator;
use LaraSpells\Generator\Generators\CreateRequestGenerator;
use LaraSpells\Generator\Generators\DocblockGenerator;
use LaraSpells\Generator\Generators\MigrationGenerator;
use LaraSpells\Generator\Generators\ModelGenerator;
use LaraSpells\Generator\Generators\RouteGenerator;
use LaraSpells\Generator\Generators\ServiceProviderGenerator;
use LaraSpells\Generator\Generators\UpdateRequestGenerator;
use LaraSpells\Generator\Generators\ViewCreateGenerator;
use LaraSpells\Generator\Generators\ViewDetailGenerator;
use LaraSpells\Generator\Generators\ViewEditGenerator;
use LaraSpells\Generator\Generators\ViewListGenerator;
use LaraSpells\Generator\Schema\Schema;
use LaraSpells\Generator\Schema\Table;
use LaraSpells\Generator\Stub;
use LaraSpells\Generator\Template;
use LaraSpells\Generator\Traits\TemplateUtil;
use Symfony\Component\Yaml\Yaml;

class ShowCommand extends SchemaBasedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        spell:show
        {schema : Path to schema (yml) file.}
        {key? : Key to show.}
        {--O|original : Show schema before resolved.}
        {--o|only= : Show only specified keys. Separeted by comma.}
        {--e|except= : Ignoring some keys. Separated by comma.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show key value from resolved schema.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();
        app()->instance(static::class, $this);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $schemaFile = $this->argument('schema');
        $key = $this->argument('key');
        $only = $this->option('only');
        $except = $this->option('except');
        $original = $this->option('original');

        // Initialize template and schema.
        $this->initializeSchema($schemaFile);
        $schema = $original ? $this->originalSchema : $this->getSchema()->toArray();

        if ($key) {
            if (!array_has($schema, $key)) {
                return $this->error("\n This schema doesn't have key '{$key}'\n");
            } else {
                $value = array_get($schema, $key);
            }
        } else {
            $value = $schema;
        }

        if ($only) {
            $value = array_only($value, explode(',', $only));
        }

        if ($except) {
            $value = array_except($value, explode(',', $except));
        }

        dd($value);
    }

}
