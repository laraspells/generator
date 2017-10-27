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
     * Route generator collector
     */
    protected $routeCollector;

    /**
     * Route crud group
     */
    protected $routeCrud;

    /**
     * Get Route Collector
     */
    public function getRouteCollector()
    {
        if (!$this->routeCollector) {
            $this->initRouteGenerator();
        }
        return $this->routeCollector;
    }

    /**
     * Get Route CRUD
     */
    public function getRouteCrud()
    {
        if (!$this->routeCrud) {
            $this->initRouteGenerator();
        }
        return $this->routeCrud;
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
                'path' => 'view/{'.$pk.'}',
                'uses' => $crudController.'@pageDetail',
                'name' => $resolveName($crudRouteNames['page_detail'])
            ];
        }

        if (empty($missingRoutes)) return;

        $group = $this->addRouteGroup([
            'name' => $routeCrudName,
            'prefix' => $routeCrudPrefix
        ]);

        foreach($missingRoutes as $route) {
            $group->addRoute($route['method'], $route['path'], $route['uses'], [
                'name' => $route['name']
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
        return $this->getRouteCrud()->addRoute($method, $path, $uses, $options);
    }

    public function addRouteGroup(array $options = [])
    {
        return $this->getRouteCrud()->addRoutegroup($options);
    }

    protected function initRouteGenerator()
    {
        $schema = $this->getSchema();
        $this->routeCollector = new RouteGeneratorCollector;
        $this->routeCrud = $this->getRouteCollector()->addRouteGroup([
            'namespace' => $schema->getRouteNamespace(),
            'name' => $schema->getRouteName() ?: null,
            'prefix' => $schema->getRoutePrefix() ?: null,
            'middleware' => $schema->getRouteMiddleware() ?: null,
            'domain' => $schema->getRouteDomain() ?: null
        ]);
    }

    protected function countAddedRoutes()
    {
        return count($this->getRouteCollector()->getRoutes());
    }

    protected function generateAddedRoutes()
    {
        if ($this->countAddedRoutes() > 0) {
            $docblock = new DocblockGenerator;
            $authorName = $this->getSchema()->getAuthorName();
            $authorEmail = $this->getSchema()->getAuthorEmail();
            $codeRoutes = implode("\n\n", array_filter(array_map(function($route) {
                return $route->generateCode();
            }, $this->getRouteCollector()->getRoutes()), function($code) {
                return strlen(trim($code)) > 0;
            }));

            if (trim($codeRoutes)) {
                $docblock->addText("Generated by LaraSpell");
                $docblock->addAnnotation("author", "{$authorName}<{$authorEmail}>");
                $docblock->addAnnotation("added", date('Y-m-d H:i'));
                $this->writeOrAppendRouteFile("\n\n".$docblock->generateCode());
            }

            $this->writeOrAppendRouteFile("\n".$codeRoutes);
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

}
