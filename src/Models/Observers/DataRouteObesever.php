<?php

namespace TCG\Voyager\Models\Observers;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Models\DataRoute;
use TCG\Voyager\Models\DataType;

class DataRouteObesever
{
	/**
	 * Handle the Model "saved" event.
	 *
	 * @param Model $model
	 */
	public function saved(Model $model)
	{
		$this->forgetCache($model);
	}

	/**
	 * Handle the Model "deleted" event.
	 *
	 * @param Model $model
	 * @return void
	 * @throws \Exception
	 */
	public function deleted(Model $model)
	{
		$this->forgetCache($model);
	}

	/**
	 * Forget cache
	 *
	 * @param Model $model
	 */
	protected function forgetCache(Model $model)
	{
		$table = $model->getTable();
		$dataType = DataType::where("name", $table)->first();
		if(! $dataType)
		{
			return;
		}

		$dataRoute = DataRoute::where("data_type_id", $dataType->getKey())->first();
		if(!$dataRoute)
		{
			return;
		}

		$store = cache();
		$name = $dataRoute->name ? $dataRoute->name : $dataType->name;
		$store_name = config("cache.web:" . $name);
		if(!$store_name)
		{
			$store_name = config("cache.web");
		}

		if($store_name)
		{
			$store = $store->store($store_name);
		}

		// locales
		$locales = config("voyager.multilingual", []);
		if(isset($locales["enabled"]) && $locales["enabled"])
		{
			$locales = (array) $locales["locales"];
		}
		else
		{
			$locales = [
				config("app.locale", "en")
			];
		}

		$slug = $model->slug;
		foreach($locales as $locale)
		{
			$store->forget($table . '/' . $locale . '/index' );
			$store->forget($table . '/' . $locale . '/child:' . $slug );
		}
	}
}