<?php

namespace LaraSpell\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use LaraSpell\Exceptions\InvalidTemplateException;
use LaraSpell\Generator;
use LaraSpell\Generators\CodeGenerator;
use LaraSpell\Generators\ControllerGenerator;
use LaraSpell\Generators\CreateRequestGenerator;
use LaraSpell\Generators\DocblockGenerator;
use LaraSpell\Generators\MigrationGenerator;
use LaraSpell\Generators\ModelGenerator;
use LaraSpell\Generators\RepositoryClassGenerator;
use LaraSpell\Generators\RepositoryInterfaceGenerator;
use LaraSpell\Generators\RouteGenerator;
use LaraSpell\Generators\ServiceProviderGenerator;
use LaraSpell\Generators\UpdateRequestGenerator;
use LaraSpell\Generators\ViewCreateGenerator;
use LaraSpell\Generators\ViewDetailGenerator;
use LaraSpell\Generators\ViewEditGenerator;
use LaraSpell\Generators\ViewListGenerator;
use LaraSpell\Schema\Schema;
use LaraSpell\Schema\Table;
use LaraSpell\Stub;
use LaraSpell\Template;
use LaraSpell\Traits\TemplateUtil;
use Symfony\Component\Yaml\Yaml;

class GenerateCommand extends SchemaBasedCommand
{
    use Concerns\GeneratorBinder,
        Concerns\RouteUtils,
        Concerns\MigrationUtils,
        Concerns\ConfigUtils;

    const HOOK_BEFORE_GENERATE_CRUDS    = 'BEFORE_GENERATE_CRUDS';
    const HOOK_BEFORE_EACH_CRUD         = 'BEFORE_EACH_CRUD';
    const HOOK_AFTER_EACH_CRUD          = 'AFTER_EACH_CRUD';
    const HOOK_AFTER_GENERATE_CRUDS     = 'AFTER_GENERATE_CRUDS';
    const HOOK_BEFORE_REPORTS           = 'BEFORE_REPORTS';
    const HOOK_END                      = 'END';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        spell:generate
        {schema : Path to schema (yml) file}
        {--replace-all : Replace existing files}
        {--askme : Ask before generate existing files}
        {--no-migration : Generate without migration}
        {--no-public : Generate without publish template public files}
        {--no-views : Generate without publish template view files}
        {--t|table= : Generate specific table}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD from given schema';

    protected $router;
    protected $menu = [];
    protected $repositories = [];
    protected $missingRoutes = [];
    protected $generatedFiles = [];
    protected $modifiedFiles = [];
    protected $addedFiles = [];
    protected $generatedMigrations = [];
    protected $suggestions = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();
        $this->router = $router;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tableName = $this->option('table');
        $schemaFile = $this->argument('schema');
        $this->initializeSchema($schemaFile);
        $this->runGenerators($tableName);
        $this->showResult();
    }

    /**
     * Get tables to generate
     *
     * @return array of LaraSpell\Schema\Table
     */
    protected function getTablesToGenerate()
    {
        $specificTable = $this->option('table');
        if ($specificTable) {
            $table = $this->getSchema()->getTable($specificTable);
            if (!$table) {
                throw new \InvalidArgumentException("Table '{$specificTable}' is not defined in schema");
            }
            $tables = [$table];
        } else {
            $tables = $this->getSchema()->getTables();
        }

        return $tables;
    }

    /**
     * Run generator.
     *
     * @param  string $class
     * @param  array $params
     * @return string
     */
    public function runGenerator($class, array $params = [])
    {
        $generator = $this->makeGenerator($class, $params);
        return $generator->generateCode();
    }

    /**
     * Generate and publish files
     *
     * @return void
     */
    protected function runGenerators()
    {
        $tables = $this->getTablesToGenerate();
        $this->applyHook(self::HOOK_BEFORE_GENERATE_CRUDS, [$tables]);
        foreach($tables as $table) {
            $this->applyHook(self::HOOK_BEFORE_EACH_CRUD, [$table]);
            $this->generateCrudForTable($table);
            $this->applyHook(self::HOOK_AFTER_EACH_CRUD, [$table]);
        }
        $this->applyHook(self::HOOK_AFTER_GENERATE_CRUDS, [$tables]);

        $this->generateBaseRepository();
        $this->generateAddedRoutes();
        $this->persistConfigs();
        $this->generateProvider();
        if (!$this->option('no-views')) {
            $this->publishViewFiles();
        }
        if (!$this->option('no-public')) {
            $this->publishPublicFiles();
        }
    }

    /**
     * Show results
     *
     * @return void
     */
    protected function showResult()
    {
        $this->applyHook(self::HOOK_BEFORE_REPORTS);

        // Show info count affected files
        print(PHP_EOL);
        $this->info("DONE!");
        $countGenerateds = count($this->generatedFiles);
        $countAddeds = count($this->addedFiles);
        $countModifieds = count($this->modifiedFiles);
        $this->info("> {$countGenerateds} ".($countGenerateds > 1? 'files generateds.' : 'file generated.'));
        $this->info("> {$countAddeds} ".($countAddeds > 1? 'files addeds.' : 'file added.'));
        $this->info("> {$countModifieds} ".($countModifieds > 1? 'files overwriteds' : 'file overwrited'));

        // Show suggestions
        $this->showSuggestions();
        $this->applyHook(self::HOOK_END);
    }

    /**
     * Get suggestions
     *
     * @return array
     */
    protected function getSuggestions()
    {
        $suggestions = $this->suggestions;

        // Suggestion missing disks
        $missingDisks = $this->getMissingDisks();
        $providers = config('app.providers');
        $providerClass = $this->getSchema()->getServiceProviderClass();
        $generatedMigrations = $this->generatedMigrations;
        if (count($missingDisks)) {
            $disks = [];
            $code = new CodeGenerator;
            foreach($missingDisks as $disk => $option) {
                $columns = $option['columns'];
                $disks[] = "'{$disk}' => ".$code->phpify($option['config'], true);
            }
            $code->addStatements("
                // Find this section
                'disks' => [
                    ...
                    // Add codes below
                    ".implode(",".PHP_EOL, $disks)."
                    // To this
                ]
            ");
            $suggestions[] = "Add ".(count($disks) > 1? 'disks' : 'disk')." configuration to your 'config/filesystems.php':".PHP_EOL.$code->generateCode();
        }

        // Suggestion register provider
        if (!in_array($providerClass, $providers)) {
            $suggestions[] = "Add provider '{$providerClass}' to your 'config/app.php'.";
        }

        // Suggestion register provider
        if (count($this->generatedMigrations)) {
            $suggestions[] = "Run 'php artisan migrate' to run generated migrations.";
        }

        return $suggestions;
    }

    /**
     * Display suggestions
     *
     * @return void
     */
    protected function showSuggestions()
    {
        $suggestions = $this->getSuggestions();
        $lineLength = 80;
        if (!empty($suggestions)) {
            print(PHP_EOL);
            $this->warn(str_repeat("=", $lineLength));
            $this->warn(" WHAT NEXT?");
            $this->warn(str_repeat("-", $lineLength));
            foreach($suggestions as $i => $suggestion) {
                $n = 0;
                $lines = explode("\n", $suggestion);
                foreach($lines as $line) {
                    $_lines = $this->chunkWords($line, $lineLength);
                    foreach($_lines as $_line) {
                        if ($n === 0) {
                            $this->warn(" ".($i+1).") ".$_line);
                        } else {
                            $this->warn("    ".$_line);
                        }
                        $n++;
                    }
                }
                $this->warn(str_repeat("-", $lineLength));
            }
            $this->warn(str_repeat("=", $lineLength));
        }
    }

    /**
     * Generate CRUD for specific table
     *
     * @param  LaraSpell\Schema\Table $table
     * @return void
     */
    protected function generateCrudForTable(Table $table)
    {
        app()->instance(Table::class, $table);

        $migration = !$this->option('no-migration');
        if ($migration) {
            $this->generateMigrationForTable($table);
        }
        $this->generateControllerForTable($table);
        $this->generateCreateRequestForTable($table);
        $this->generateUpdateRequestForTable($table);
        $this->generateModelForTable($table);
        $this->generateRepositoryInterfaceForTable($table);
        $this->generateRepositoryClassForTable($table);
        $this->generateViews($table);
        $this->addCrudMissingRoutes($table);
        $this->addConfigRepository($table->getRepositoryInterface(), $table->getRepositoryClass());
        $this->addConfigMenu($table->getRouteListName(), $table->getLabel(), ['icon' => $table->get('icon')]);
    }

    /**
     * Generate Migration for specific table
     *
     * @return void
     */
    protected function generateMigrationForTable(Table $table)
    {
        $ask = $this->option('askme');
        $replace = $this->option('replace-all');
        $existingFile = $this->getExistingMigrationFile($table);
        $filePath = $table->getMigrationPath();
        $tableName = $table->getName();
        $action = "[generate]";
        $shouldGenerate = false;
        if ($existingFile) {
            $filePath = $existingFile;
            if ($replace) {
                $action = "[overwrite]";
                $shouldGenerate = true;
            } elseif ($ask) {
                $replace = $this->confirm("Migration file that create table \"{$tableName}\" already exists. Do you want to replace it?", true);
                if ($replace) {
                    $action = "[overwrite]";
                    $shouldGenerate = true;
                }
            }
        } else {
            $shouldGenerate = true;
        }

        if (!$shouldGenerate) return;
        $content = $this->runGenerator(MigrationGenerator::class);

        $this->writeFile($filePath, $content);
        switch($action) {
            case "[overwrite]": $this->addModifiedFile($filePath); break;
            case "[generate]":
                $this->generatedMigrations[] = $filePath;
                $this->addAddedFile($filePath);
                break;
        }
    }

    protected function generateControllerForTable(Table $table)
    {
        $filePath = $table->getControllerPath();
        $content = $this->runGenerator(ControllerGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function generateCreateRequestForTable(Table $table)
    {
        $filePath = $table->getCreateRequestPath();
        $content = $this->runGenerator(CreateRequestGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function generateUpdateRequestForTable(Table $table)
    {
        $filePath = $table->getUpdateRequestPath();
        $content = $this->runGenerator(UpdateRequestGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function generateModelForTable(Table $table)
    {
        $filePath = $table->getModelPath();
        $content = $this->runGenerator(ModelGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function generateRepositoryInterfaceForTable(Table $table)
    {
        $filePath = $table->getRepositoryInterfacePath();
        $content = $this->runGenerator(RepositoryInterfaceGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function generateRepositoryClassForTable(Table $table)
    {
        $filePath = $table->getRepositoryClassPath();
        $content = $this->runGenerator(RepositoryClassGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function generateBaseRepository()
    {
        $stub = new Stub(file_get_contents(__DIR__.'/../stubs/BaseRepository.php.stub'));
        $filePath = $this->getSchema()->getRepositoryClassPath('BaseRepository.php');
        $content = $stub->render([
            'namespace' => $this->getSchema()->getRepositoryNamespace().'\\Eloquent',
            'classname' => 'BaseRepository'
        ]);
        $this->generateFile($filePath, $content);
    }

    protected function generateViews(Table $table)
    {
        $views = [
            $table->getViewListPath() => $this->runGenerator(ViewListGenerator::class, [
                'stubContent' => $this->getTemplate()->getStubContent('page-list.stub')
            ]),
            $table->getViewDetailPath() => $this->runGenerator(ViewDetailGenerator::class, [
                'stubContent' => $this->getTemplate()->getStubContent('page-detail.stub')
            ]),
            $table->getViewCreatePath() => $this->runGenerator(ViewCreateGenerator::class, [
                'stubContent' => $this->getTemplate()->getStubContent('form-create.stub')
            ]),
            $table->getViewEditPath() => $this->runGenerator(ViewEditGenerator::class, [
                'stubContent' => $this->getTemplate()->getStubContent('form-edit.stub')
            ]),
        ];

        foreach($views as $path => $content) {
            $this->generateFile($path, $content);
        }
    }

    protected function generateAddedRoutes()
    {
        if ($this->countAddedRoutes() > 0) {
            $docblock = new DocblockGenerator;
            $authorName = $this->getSchema()->getAuthorName();
            $authorEmail = $this->getSchema()->getAuthorEmail();
            $docblock->addText("Generated by LaraSpell");
            $docblock->addAnnotation("author", "{$authorName}<{$authorEmail}>");
            $docblock->addAnnotation("added", date('Y-m-d H:i'));
            $this->writeOrAppendRouteFile("\n".$docblock->generateCode());
            $this->writeOrAppendRouteFile($this->getRouteGeneratorOutside()->generateCode());
        }
    }

    /**
     * Write or append route file
     *
     * @param  string $code
     * @return void
     */
    protected function writeOrAppendRouteFile($code)
    {
        $routeFile = $this->getSchema()->getRouteFile();
        $routeFileExists = $this->hasFile($routeFile);

        if ($routeFileExists) {
            return $this->appendFile($routeFile, $code);
        } else {
            return $this->writeFile($routeFile, "<?php\n\n".$code);
        }
    }

    protected function addRoutesToGenerator(array $routes, RouteGenerator $generator)
    {
        foreach($routes as $route) {
            $generator->addRoute($route['method'], $route['path'], $route['uses'], [
                'name' => $route['name']
            ]);
        }
    }

    protected function addRouteGroupsToGenerator(array $groups, RouteGenerator $generator)
    {
        foreach($groups as $group) {
            $generator->addGroup(array_merge([
                'name' => array_get($group, 'name'),
            ], $group['options']), function($routeGenerator) use ($group) {
                $routes = $group['routes'];
                foreach($routes as $route) {
                    $routeGenerator->addRoute($route['method'], $route['path'], $route['uses'], [
                        'name' => $route['name']
                    ]);
                }
            });
        }
    }

    protected function generateProvider()
    {
        $filePath = $this->getSchema()->getServiceProviderPath();
        $content = $this->runGenerator(ServiceProviderGenerator::class);
        $this->generateFile($filePath, $content);
    }

    protected function publishViewFiles()
    {
        $template = $this->getTemplate();
        $viewPath = $this->getSchema()->getViewpath();
        $templateViewDir = $template->getDirectory().'/'.$template->getViewDirectory();
        $viewFiles = $template->getViewFiles();
        $viewNamespace = $this->getSchema()->getViewNamespace();
        $configKey = str_replace("/", ".", preg_replace("/\.php$/", "", $this->getSchema()->getConfigFile()));
        $data = [
            'view_namespace' => $viewNamespace? $viewNamespace.'::' : '',
            'config_key' => $configKey,
            'schema' => $this->getSchema()->toArray()
        ];
        foreach($viewFiles as $viewFile) {
            $dest = $viewPath.'/'.ltrim(str_replace($templateViewDir, "", $viewFile), '/');
            $stub = new Stub(file_get_contents($viewFile), $data);
            $content = $stub->render();
            $this->generateFile($dest, $content);
        }
    }

    protected function publishPublicFiles()
    {
        $replace = $this->option('replace-all');
        $template = $this->getTemplate();
        $publicFiles = $template->getPublicFiles();
        $publicPath = 'public';
        $templatePublicPath = $template->getDirectory().'/'.$template->getPublicDirectory();
        foreach($publicFiles as $publicFile) {
            $dest = $publicPath.'/'.ltrim(str_replace($templatePublicPath, '', $publicFile), '/');
            if (file_exists(base_path($dest))) {
                if ($replace) {
                    $this->copyFile($publicFile, $dest);
                    $this->addModifiedFile($dest);
                }
            } else {
                $this->copyFile($publicFile, $dest);
                $this->addAddedFile($dest);
            }
        }
    }

    protected function generateFile($filepath, $content)
    {
        $ask = $this->option('askme');
        $replace = $this->option('replace-all');
        $exists = $this->hasFile($filepath);

        if (!$exists) {
            $this->writeFile($filepath, $content);
            $this->addGeneratedFile($filepath);
        } elseif ($replace) {
            $this->writeFile($filepath, $content);
            $this->addModifiedFile($filepath);
        } elseif ($ask) {
            $replace = $this->confirm("File \"{$filepath}\" already exists. Do you want to replace it?", false);
            if ($replace) {
                $this->writeFile($filepath, $content);
                $this->addModifiedFile($filepath);
            }
        }
    }

    protected function copyFile($from, $to)
    {
        $this->makeDirectoryIfNotExists($to);
        copy($from, base_path($to));
    }

    protected function writeFile($path, $content)
    {
        $path = ltrim($path, "/");
        $this->makeDirectoryIfNotExists($path);

        return file_put_contents(base_path($path), $content);
    }

    protected function makeDirectoryIfNotExists($path)
    {
        $paths = explode("/", $path);
        $filename = array_pop($paths);
        $dir = [];

        // Make directories if not exists
        while($dir[] = array_shift($paths)) {
            $directory = base_path(implode('/', $dir));
            if (!is_dir($directory)) {
                mkdir($directory);
            }
        }
    }

    protected function appendFile($path, $content)
    {
        return file_put_contents(base_path($path), $content, FILE_APPEND);
    }

    protected function hasFile($path)
    {
        return file_exists(base_path($path));
    }

    protected function getFileContent($path)
    {
        return file_get_contents(base_path($path));
    }

    protected function addGeneratedFile($file, $info = true)
    {
        $this->generatedFiles[] = $file;
        if ($info) {
            $this->info("- [generate] {$file}");
        }
    }

    protected function addAddedFile($file, $info = true)
    {
        $this->addedFiles[] = $file;
        if ($info) {
            $this->info("- [added] {$file}");
        }
    }

    protected function addModifiedFile($file, $info = true)
    {
        $this->modifiedFiles[] = $file;
        if ($info) {
            $this->info("- [overwrite] {$file}");
        }
    }

    protected function getAddedFiles()
    {
        return $this->addedFiles;
    }

    protected function getModifiedFiles()
    {
        return $this->modifiedFiles;
    }

    /**
     * Get missing filesystem disks
     *
     * @return array
     */
    protected function getMissingDisks()
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
                            'columns' => [],
                            'config' => [
                                'driver' => 'local',
                                'root' => "eval(\"public_path('{$diskNamePlural}')\")",
                                'url' => "eval(\"env('APP_URL').'/{$diskNamePlural}'\")",
                                'visibility' => 'public'
                            ],
                        ];
                    }
                    $missingDisks[$disk]['columns'][] = $table->getName().'.'.$field->getColumnName();
                }
            }
        }

        return $missingDisks;
    }

    protected function chunkWords($text, $length)
    {
        $words = explode(" ", $text);
        $lines = [];
        $line = 0;
        foreach($words as $i => $word) {
            if (!isset($lines[$line])) {
                $lines[$line] = $word;
            } else {
                $lineText = $lines[$line];

                if (strlen($lineText.' '.$word) > $length) {
                    $line++;
                    $lines[$line] = $word;
                } else {
                    $lines[$line] .= ' '.$word;
                }
            }
        }

        return $lines;
    }

}
