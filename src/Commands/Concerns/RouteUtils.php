<?php

namespace LaraSpell\Commands\Concerns;

use Closure;
use InvalidArgumentException;
use LaraSpell\Generators\RouteGenerator;
use LaraSpell\Schema\Table;
use UnexpectedValueException;

trait Routeutils
{

    /**
     * Route generator inside
     *
     * @return void
     */
    protected $routeGeneratorInside;

    /**
     * Route generator outside
     *
     * @return void
     */
    protected $routeGeneratorOutside;

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
     * @param  LaraSpell\Schema\Table $table
     * @param  bool $withNamespace
     * @return array
     */
    public function addCrudMissingRoutes(Table $table)
    {
        $rootSchema = $table->getRootSchema();
        $pk = $table->getPrimaryColumn();
        $routeBaseName = $table->getRouteName('', true);        // e.g: "blog::posts."
        $routeCrudName = $table->getRouteName('', false);       // e.g: "posts."
        $routeCrudPrefix = $table->getRoutePrefix();            // e.g: "posts"
        $crudController = $table->getControllerClass(false);    // e.g: "PostController"
        $missingRoutes = [];
        $crudRouteNames = [
            'list' => $table->getRouteListName(true),               // e.g: "blog::posts.page-list"
            'page_detail' => $table->getRouteDetailName(true),      // e.g: "blog::posts.page-detail"
            'form_create' => $table->getRouteCreateName(true),      // e.g: "blog::posts.form-create"
            'post_create' => $table->getRoutePostCreateName(true),  // e.g: "blog::posts.post-create"
            'form_edit' => $table->getRouteEditName(true),          // e.g: "blog::posts.form-edit"
            'post_edit' => $table->getRoutePostEditName(true),      // e.g: "blog::posts.post-edit"
            'delete' => $table->getRouteDeleteName(true),           // e.g: "blog::posts.delete"
        ];
        $resolveName = function($name) use ($routeBaseName) {
            return substr($name, strlen($routeBaseName));
        };

        if (!$this->hasRouteNamed($crudRouteNames['list'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => '/',
                'uses' => $crudController.'@pageList',
                'name' => $resolveName($crudRouteNames['list'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['form_create'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => 'create',
                'uses' => $crudController.'@formCreate',
                'name' => $resolveName($crudRouteNames['form_create'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['post_create'])) {
            $missingRoutes[] = [
                'method' => 'post',
                'path' => 'create',
                'uses' => $crudController.'@postCreate',
                'name' => $resolveName($crudRouteNames['post_create'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['form_edit'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => 'edit/{'.$pk.'}',
                'uses' => $crudController.'@formEdit',
                'name' => $resolveName($crudRouteNames['form_edit'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['post_edit'])) {
            $missingRoutes[] = [
                'method' => 'post',
                'path' => 'edit/{'.$pk.'}',
                'uses' => $crudController.'@postEdit',
                'name' => $resolveName($crudRouteNames['post_edit'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['delete'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => 'delete/{'.$pk.'}',
                'uses' => $crudController.'@delete',
                'name' => $resolveName($crudRouteNames['delete'])
            ];
        }

        if (!$this->hasRouteNamed($crudRouteNames['page_detail'])) {
            $missingRoutes[] = [
                'method' => 'get',
                'path' => '{'.$pk.'}',
                'uses' => $crudController.'@pageDetail',
                'name' => $resolveName($crudRouteNames['page_detail'])
            ];
        }

        if (empty($missingRoutes)) return;

        $this->addRouteGroup([
            'name' => $routeCrudName,
            'prefix' => $routeCrudPrefix
        ], function($crudRouteGenerator) use ($missingRoutes) {
            foreach($missingRoutes as $route) {
                $crudRouteGenerator->addRoute($route['method'], $route['path'], $route['uses'], [
                    'name' => $route['name']
                ]);
            }
        });
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
        dd(func_get_args());
        return $this->getRouteGeneratorInside()->addRoute($method, $path, $uses, $options);
    }

    public function addRouteGroup(array $options = [], Closure $callback)
    {
        return $this->getRouteGeneratorInside()->addGroup($options, $callback);
    }

    public function getRouteGeneratorInside()
    {
        if (!$this->routeGeneratorInside) {
            $this->initRouteGenerators();
        }

        return $this->routeGeneratorInside;
    }

    public function getRouteGeneratorOutside()
    {
        if (!$this->routeGeneratorOutside) {
            $this->initRouteGenerators();
        }

        return $this->routeGeneratorOutside;
    }

    protected function initRouteGenerators()
    {
        $schema = $this->getSchema();
        $this->routeGeneratorOutside = $this->makeGenerator(RouteGenerator::class);
        $this->routeGeneratorInside = $this->routeGeneratorOutside->addGroup([
            'namespace' => $schema->getRouteNamespace(),
            'name' => $schema->getRouteName() ?: null,
            'prefix' => $schema->getRoutePrefix() ?: null,
            'middleware' => $schema->getRouteMiddleware() ?: null,
            'domain' => $schema->getRouteDomain() ?: null
        ], function() {

        });
    }

    protected function countAddedRoutes()
    {
        return $this->getRouteGeneratorOutside()->getCountRoutes();
    }

}
