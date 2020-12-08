<?php

namespace TCG\Voyager;

use Arrilot\Widgets\Facade as Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Actions\DeleteAction;
use TCG\Voyager\Actions\EditAction;
use TCG\Voyager\Actions\RestoreAction;
use TCG\Voyager\Actions\ViewAction;
use TCG\Voyager\Events\AlertsCollection;
use TCG\Voyager\FormFields\After\HandlerInterface as AfterHandlerInterface;
use TCG\Voyager\FormFields\HandlerInterface;
use TCG\Voyager\Models\Category;
use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\DataRoute;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Observers\DataRouteObesever;
use TCG\Voyager\Models\Page;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\Setting;
use TCG\Voyager\Models\Translation;
use TCG\Voyager\Models\User;
use TCG\Voyager\Traits\Translatable;

class Voyager
{
    protected $version;
    protected $filesystem;

    protected $alerts = [];
    protected $alertsCollected = false;

    protected $formFields = [];
    protected $afterFormFields = [];

    protected $viewLoadingEvents = [];

    protected $actions = [
        DeleteAction::class,
        RestoreAction::class,
        EditAction::class,
        ViewAction::class,
    ];

    protected $models = [
        'Category'    => Category::class,
        'DataRow'     => DataRow::class,
        'DataType'    => DataType::class,
        'DataRoute'   => DataRoute::class,
        'Menu'        => Menu::class,
        'MenuItem'    => MenuItem::class,
        'Page'        => Page::class,
        'Permission'  => Permission::class,
        'Post'        => Post::class,
        'Role'        => Role::class,
        'Setting'     => Setting::class,
        'User'        => User::class,
        'Translation' => Translation::class,
    ];

	protected $routes = [];

    public $setting_cache = null;

    public function __construct()
    {
        $this->filesystem = app(Filesystem::class);

        $this->findVersion();
    }

    public function model($name)
    {
        return app($this->models[Str::studly($name)]);
    }

    public function modelClass($name)
    {
        return $this->models[$name];
    }

    public function useModel($name, $object)
    {
        if (is_string($object)) {
            $object = app($object);
        }

        $class = get_class($object);

        if (isset($this->models[Str::studly($name)]) && !$object instanceof $this->models[Str::studly($name)]) {
            throw new \Exception("[{$class}] must be instance of [{$this->models[Str::studly($name)]}].");
        }

        $this->models[Str::studly($name)] = $class;

        return $this;
    }

    public function view($name, array $parameters = [])
    {
        foreach (Arr::get($this->viewLoadingEvents, $name, []) as $event) {
            $event($name, $parameters);
        }

        return view($name, $parameters);
    }

    public function onLoadingView($name, \Closure $closure)
    {
        if (!isset($this->viewLoadingEvents[$name])) {
            $this->viewLoadingEvents[$name] = [];
        }

        $this->viewLoadingEvents[$name][] = $closure;
    }

    public function formField($row, $dataType, $dataTypeContent)
    {
        $formField = $this->formFields[$row->type];

        return $formField->handle($row, $dataType, $dataTypeContent);
    }

    public function afterFormFields($row, $dataType, $dataTypeContent)
    {
        return collect($this->afterFormFields)->filter(function ($after) use ($row, $dataType, $dataTypeContent) {
            return $after->visible($row, $dataType, $dataTypeContent, $row->details);
        });
    }

    public function addFormField($handler)
    {
        if (!$handler instanceof HandlerInterface) {
            $handler = app($handler);
        }

        $this->formFields[$handler->getCodename()] = $handler;

        return $this;
    }

    public function addAfterFormField($handler)
    {
        if (!$handler instanceof AfterHandlerInterface) {
            $handler = app($handler);
        }

        $this->afterFormFields[$handler->getCodename()] = $handler;

        return $this;
    }

    public function formFields()
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver", 'mysql');

        return collect($this->formFields)->filter(function ($after) use ($driver) {
            return $after->supports($driver);
        });
    }

    public function addAction($action)
    {
        array_push($this->actions, $action);
    }

    public function replaceAction($actionToReplace, $action)
    {
        $key = array_search($actionToReplace, $this->actions);
        $this->actions[$key] = $action;
    }

    public function actions()
    {
        return $this->actions;
    }

    /**
     * Get a collection of dashboard widgets.
     * Each of our widget groups contain a max of three widgets.
     * After that, we will switch to a new widget group.
     *
     * @return array - Array consisting of \Arrilot\Widget\WidgetGroup objects
     */
    public function dimmers()
    {
        $widgetClasses = config('voyager.dashboard.widgets');
        $dimmerGroups = [];
        $dimmerCount = 0;
        $dimmers = Widget::group("voyager::dimmers-{$dimmerCount}");

        foreach ($widgetClasses as $widgetClass) {
            $widget = app($widgetClass);

            if ($widget->shouldBeDisplayed()) {

                // Every third dimmer, we consider out WidgetGroup filled.
                // We switch that out with another WidgetGroup.
                if ($dimmerCount % 3 === 0 && $dimmerCount !== 0) {
                    $dimmerGroups[] = $dimmers;
                    $dimmerGroupTag = ceil($dimmerCount / 3);
                    $dimmers = Widget::group("voyager::dimmers-{$dimmerGroupTag}");
                }

                $dimmers->addWidget($widgetClass);
                $dimmerCount++;
            }
        }

        $dimmerGroups[] = $dimmers;

        return $dimmerGroups;
    }

    public function setting($key, $default = null)
    {
        $globalCache = config('voyager.settings.cache', false);

        if ($globalCache && Cache::tags('settings')->has($key)) {
            return Cache::tags('settings')->get($key);
        }

        if ($this->setting_cache === null) {
            if ($globalCache) {
                // A key is requested that is not in the cache
                // this is a good opportunity to update all keys
                // albeit not strictly necessary
                Cache::tags('settings')->flush();
            }

            foreach (self::model('Setting')->orderBy('order')->get() as $setting) {
                $keys = explode('.', $setting->key);
                if(!isset($this->setting_cache))
                {
	                $this->setting_cache = [];
                }
	            $group_name = $keys[0];
                if(!isset($this->setting_cache[$group_name]))
                {
	                $this->setting_cache[$group_name] = [];
                }
                $this->setting_cache[$group_name][$keys[1] ?? ""] = $setting->value;

                if ($globalCache) {
                    Cache::tags('settings')->forever($setting->key, $setting->value);
                }
            }
        }

        $parts = explode('.', $key);

        if (count($parts) == 2) {
            return @ $this->setting_cache[$parts[0]][$parts[1]] ?: $default;
        } else {
            return @ $this->setting_cache[$parts[0]] ?: $default;
        }
    }

    public function image($file, $default = '')
    {
        if (!empty($file)) {
            return str_replace('\\', '/', Storage::disk(config('voyager.storage.disk'))->url($file));
        }

        return $default;
    }

	public function routes()
	{
		require __DIR__.'/../routes/voyager.php';
	}

	private function webRouteRule(\ReflectionMethod $method, $route, $use_slug, $as, $uses)
	{
		$rule = [
			"route" => $route,
			"as" => $as,
			"uses" => $uses,
			"where" => null,
			"method" => null,
		];

		$comment = $method->getDocComment();
		if($comment)
		{
			if($use_slug && preg_match('/@route_where (.+)(?:$|\n|\r)/', $comment, $m))
			{
				$where = trim($m[1]);
				if($where)
				{
					$rule["where"] = $where;
				}
			}

			if(preg_match('/@route_method (.+)(?:$|\n|\r)/', $comment, $m))
			{
				$method = trim($m[1]);
				$method = Str::lower($method);
				$method = preg_replace('/\s+/', '', $method);
				if($method !== "get")
				{
					$rule["method"] = explode(",", $method);
				}
			}
		}

		return (object) $rule;
	}

	public function webRoutesData()
	{
		return Cache::rememberForever("voyager.routers.web", function() {
			return DataRoute::orderBy("order")
				->get()
				->map(function(DataRoute $dataRoute) {

					/** @var \TCG\Voyager\Models\DataType $dataType */
					$dataType = $dataRoute->dataType()->first();
					if(!$dataType)
					{
						return null;
					}

					try {
						$ref = new \ReflectionClass($dataRoute->controller_name);
						if(!$ref->hasMethod('browse'))
						{
							return null;
						}

						$browse = $ref->getMethod('browse');
						if($browse->isStatic() || ! $browse->isPublic())
						{
							return null;
						}
					}
					catch(\ReflectionException $e) {
						return null;
					}

					$prefix = "\\" . ltrim($dataRoute->controller_name, "\\");
					$slug = trim($dataRoute->slug, "/");
					$name = $dataRoute->name ? $dataRoute->name : $dataType->name;

					$route_slug = $slug;
					$route_rule = $browse->getNumberOfParameters() > 0;
					if($route_rule)
					{
						$route_slug = $browse->getNumberOfRequiredParameters() > 0 ? "{slug}" : "{slug?}";
						if($slug)
						{
							$route_slug = $slug . "/" . $route_slug;
						}
					}
					else if(!$route_slug)
					{
						$route_slug = "/";
					}

					$route = [
						"key" => $dataRoute->getKey(),
						"key_type" => $dataType->getKey(),
						"name" => $name,
						"model_name" => $dataType->model_name,
						"controller_name" => $dataRoute->controller_name,
						"slug_field" => $dataRoute->slug_field ? $dataRoute->slug_field : "slug",
						"template" => $dataRoute->template,
						"preload" => false,
						"routes" => [
							$this->webRouteRule(
								$browse,
								$route_slug,
								$route_rule,
								$name . ".browse",
								$prefix . "@browse"
							)
						],
					];

					if($ref->hasMethod('preload'))
					{
						$method = $ref->getMethod('preload');
						if($method->isStatic() && $method->isPublic() && $method->getNumberOfParameters() === 1)
						{
							$route["preload"] = true;
						}
					}

					if($slug)
					{
						$slug .= "/";
					}

					foreach($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
					{
						$method_name = $method->getName();
						if(!$method->isStatic() && preg_match('/^browse([A-Z][a-zA-Z])$/', $method_name, $m))
						{
							$route_name = Str::snake($m[1], "_");
							$route_slug = str_replace("_", "-", $route_name);
							if($route_name !== "browse")
							{
								$route_slug = $slug . $route_slug;
								$route_rule = $method->getNumberOfParameters() > 0;
								if($route_rule)
								{
									$route_slug .= $method->getNumberOfRequiredParameters() > 0 ? "{slug}" : "{slug?}";
								}
								$route["routes"][] = $this->webRouteRule(
									$method,
									$route_slug,
									$route_rule,
									$name . "." . $route_name,
									$prefix . "@" . $method_name
								);
							}
						}
					}

					return (object) $route;
				})
				->filter(function($value) {
					return $value !== null;
				})
				->toArray();
		});
	}

	public function webRoutes()
	{
		$routes = $this->webRoutesData();
		foreach($routes as $route)
		{
			$this->routes[$route->model_name] = $route;
			$this->routes[$route->controller_name] = $route;

			$ref = new \ReflectionClass($route->model_name);
			$ref
				->getMethod('observe')
				->invoke(null, DataRouteObesever::class );

			if($route->preload)
			{
				$ref = new \ReflectionClass($route->controller_name);
				$ref->getMethod("preload")->invoke(null, $route);
			}
		}

		require __DIR__.'/../routes/web.php';
	}

	public function webRoute(string $name)
	{
		return $this->routes[$name] ?? null;
	}

    public function getVersion()
    {
        return $this->version;
    }

    public function addAlert(Alert $alert)
    {
        $this->alerts[] = $alert;
    }

    public function alerts()
    {
        if (!$this->alertsCollected) {
            event(new AlertsCollection($this->alerts));

            $this->alertsCollected = true;
        }

        return $this->alerts;
    }

    protected function findVersion()
    {
        if (!is_null($this->version)) {
            return;
        }

        if ($this->filesystem->exists(base_path('composer.lock'))) {
            // Get the composer.lock file
            $file = json_decode(
                $this->filesystem->get(base_path('composer.lock'))
            );

            // Loop through all the packages and get the version of voyager
            foreach ($file->packages as $package) {
                if ($package->name == 'tcg/voyager') {
                    $this->version = $package->version;
                    break;
                }
            }
        }
    }

    /**
     * @param string|Model|Collection $model
     *
     * @return bool
     */
    public function translatable($model)
    {
        if (!config('voyager.multilingual.enabled')) {
            return false;
        }

        if (is_string($model)) {
            $model = app($model);
        }

        if ($model instanceof Collection) {
            $model = $model->first();
        }

        if (!is_subclass_of($model, Model::class)) {
            return false;
        }

        $traits = class_uses_recursive(get_class($model));

        return in_array(Translatable::class, $traits);
    }

    public function getLocales()
    {
        $appLocales = [];
        if ($this->filesystem->exists(resource_path('lang/vendor/voyager'))) {
            $appLocales = array_diff(scandir(resource_path('lang/vendor/voyager')), ['..', '.']);
        }

        $vendorLocales = array_diff(scandir(realpath(__DIR__.'/../publishable/lang')), ['..', '.']);
        $allLocales = array_merge($vendorLocales, $appLocales);

        asort($allLocales);

        return $allLocales;
    }
}
