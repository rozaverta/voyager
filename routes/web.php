<?php
/** @var array $routes */

/*
|--------------------------------------------------------------------------
| Voyager Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may override any of the routes that are included
| with Voyager.
|
*/

$prefix = config('voyager.settings.web_prefix', null);
if($prefix) {
	$prefix = rtrim($prefix, ".") . ".";
}

Route::group(['as' => $prefix], function () use ($routes) {

	foreach($routes as $route)
	{
		foreach($route->routes as $route)
		{
			$routing = [
				"uses" => $route->uses,
				"as" => $route->as,
			];

			$router = is_array($route->method)
				? Route::match($route->method, $route->route, $routing)
				: Route::get($route->route, $routing);

			if($route->where)
			{
				$router->where("slug", $route->where);
			}
		}
	}
});