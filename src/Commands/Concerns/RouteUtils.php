<?php

namespace LaraSpells\Generator\Commands\Concerns;

use Closure;
use InvalidArgumentException;
use LaraSpells\Generator\Generators\DocblockGenerator;
use LaraSpells\Generator\Generators\RouteGenerator;
use LaraSpells\Generator\RouteGeneratorCollector;
use LaraSpells\Generator\Schema\Table;
use UnexpectedValueException;

trait RouteUtils
{

    /**
     * List of route collector instances
     */
    protected $routeCollectors = [];

    /*
     * Get default route file
     */
    public function getDefaultRouteFile()
    {
        return $this->getSchema()->get('route.file');
    }

    /**
     * Get Route Collector
     */
    public function getRouteCollector($routeFile = null)
    {
        if (!$routeFile) $routeFile = $this->getDefaultRouteFile();

        if (!isset($this->routeCollectors[$routeFile])) {
            $this->setRouteCollector($routeFile, new RouteGeneratorCollector);
        }

        return $this->routeCollectors[$routeFile];
    }

    /**
     * Add Route Collector
     */
    public function setRouteCollector($routeFile, RouteGeneratorCollector $routeCollector)
    {
        $this->routeCollectors[$routeFile] = $routeCollector;
    }

    /**
     * Get application router
     *
     * @return Illuminate\Routing\Router
     */
    public function getRouter()
    {
        return property_exists($this, 'router')? $this->router : app('router');
    }

    /**
     * Add CRUD missing routes
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @param  bool $withNamespace
     * @return array
     */
    public function addCrudMissingRoutes(Table $table)
    {
        $rootSchema = $table->getRootSchema();
        $pk = $table->getPrimaryColumn();

        $routeFile = $table->getRouteFile();                        // [table].route.file
        $routeNamespace = $table->getRouteNamespace();              // [table].route.namespace
        $routeDomain = $table->getRouteDomain();                    // [table].route.domain
        $routeMiddleware = $table->getRouteMiddleware();            // [table].route.middleware
        $routeBaseName = $table->getRouteName('', true);            // [table].route.namespace + [table].route.name
        $routePrefix = $table->getRouteFullPath();                  // [table].route.prefix + '/' + [table].route.path
        $crudController = $table->getControllerClass(false);

        $missingRoutes = [];
        $crudRouteNames = [
            'index' => $table->getRouteIndexName(true),             // e.g: "blog::posts.index"
            'show' => $table->getRouteShowName(true),               // e.g: "blog::posts.show"
            'create' => $table->getRouteCreateName(true),           // e.g: "blog::posts.create"
            'store' => $table->getRouteStoreName(true),             // e.g: "blog::posts.store"
            'edit' => $table->getRouteEditName(true),               // e.g: "blog::posts.edit"
            'update' => $table->getRouteUpdateName(true),           // e.g: "blog::posts.update"
            'destroy' => $table->getRouteDestroyName(true),         // e.g: "blog::posts.destroy"
        ];
        $resolveName = function($name) use ($routeBaseName) {
            return substr($name, strlen($routeBaseName));
        };

        if (!$this->hasRouteNamed($crudRouteNames['index'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => '',
                'uses' => $crudController.'@index',
                'name' => $resolveName($crudRouteNames['index'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['create'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => 'create',
                'uses' => $crudController.'@create',
                'name' => $resolveName($crudRouteNames['create'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['store'])) {
            $missingRoutes[] = [
                'method' => 'post',
                'path' => '',
                'uses' => $crudController.'@store',
                'name' => $resolveName($crudRouteNames['store'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['show'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => '{'.$pk.'}',
                'uses' => $crudController.'@show',
                'name' => $resolveName($crudRouteNames['show'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['edit'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => '{'.$pk.'}/edit',
                'uses' => $crudController.'@edit',
                'name' => $resolveName($crudRouteNames['edit'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['update'])) {
            $missingRoutes[] = [
                'method' => 'put',
                'path' => '{'.$pk.'}',
                'uses' => $crudController.'@update',
                'name' => $resolveName($crudRouteNames['update'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['destroy'])) {
            $missingRoutes[] = [
                'method' => 'delete',
                'path' => '{'.$pk.'}',
                'uses' => $crudController.'@destroy',
                'name' => $resolveName($crudRouteNames['destroy'])
            ];
        }

        if (empty($missingRoutes)) return;

        $group = $this->addRouteGroup([
            'name' => $routeBaseName,
            'prefix' => $routePrefix,
            'namespace' => $routeNamespace,
            'domain' => $routeDomain,
            'middleware' => $routeMiddleware,
            'file' => $routeFile,
        ]);

        foreach($missingRoutes as $route) {
            $group->addRoute($route['method'], $route['path'], $route['uses'], [
                'name' => $route['name'],
                'file' => $routeFile
            ]);
        }
    }

    /**
     * Check route by name from added routes and current registered routes
     *
     * @param  string $name
     * @return bool
     */
    public function hasRouteNamed($name)
    {
        $router = $this->getRouter();
        return $router->has($name);
    }

    public function addRoute($method, $path, $uses, array $options = [])
    {
        $options = array_merge(['file' => $this->getDefaultRouteFile()], $options);
        return $this->getRouteCollector($options['file'])->addRoute($method, $path, $uses, $options);
    }

    public function addRouteGroup(array $options = [])
    {
        $options = array_merge(['file' => $this->getDefaultRouteFile()], $options);
        return $this->getRouteCollector($options['file'])->addRoutegroup($options);
    }

    protected function countAddedRoutes($routeFile = null)
    {
        if (!$routeFile) $routeFile = $this->getDefaultRouteFile();
        return count($this->getRouteCollector($routeFile)->getRoutes());
    }

    protected function generateAddedRoutes()
    {
        foreach ($this->routeCollectors as $routeFile => $routeCollector) {
            if ($this->countAddedRoutes($routeFile) > 0) {
                $docblock = new DocblockGenerator;
                $authorName = $this->getSchema()->getAuthorName();
                $authorEmail = $this->getSchema()->getAuthorEmail();
                $codeRoutes = implode("\n\n", array_filter(array_map(function($route) {
                    return $route->generateCode();
                }, $this->getRouteCollector($routeFile)->getRoutes()), function($code) {
                    return strlen(trim($code)) > 0;
                }));

                if (trim($codeRoutes)) {
                    $docblock->addText("Generated by LaraSpells");
                    $docblock->addAnnotation("author", "{$authorName}<{$authorEmail}>");
                    $docblock->addAnnotation("added", date('Y-m-d H:i'));
                    $this->writeOrAppendRouteFile($routeFile, "\n\n".$docblock->generateCode());
                }

                $this->writeOrAppendRouteFile($routeFile, "\n".$codeRoutes);
            }
        }
    }

    /**
     * Write or append route file
     *
     * @param  string $code
     * @return void
     */
    protected function writeOrAppendRouteFile($routeFile, $code)
    {
        $routeFileExists = $this->hasFile($routeFile);

        if ($routeFileExists) {
            return $this->appendFile($routeFile, $code);
        } else {
            return $this->writeFile($routeFile, "<?php".$code);
        }
    }

}
