<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.04.2020
 * Time: 15:55
 */

namespace TCG\Voyager\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use TCG\Voyager\Models\DataRoute;

trait BreadWebController
{
	protected static $data;

	protected $cacheable_index = false;
	protected $cacheable_child = false;
	protected $cache_ttl = null; // null = forever
	protected $pagination = true;
	protected $pagination_page = 1;
	protected $pagination_pages = 1;
	protected $pagination_total = 0;
	protected $pagination_request_name = "page";
	protected $pagination_limit = 20;
	protected $sort_field_name = null;
	protected $sort_field_direction = "asc";
	protected $where = null;
	protected $json_mode = false;

	public function browse(string $slug = null)
	{
		return $slug
			? $this->childPage($slug)
			: $this->indexPage();
	}

	public static function preload($data)
	{
		self::$data = $data;
	}

	/**
	 * @param string $flag
	 * @return \Illuminate\Cache\CacheManager|\Illuminate\Contracts\Cache\Repository|null
	 * @throws \Exception
	 */
	protected function cache(string $flag)
	{
		$flag = 'cacheable_' . $flag;
		$cacheable = isset($this->{$flag}) && $this->{$flag} === true;
		if(!$cacheable)
		{
			return null;
		}

		$cache = cache();
		$store = config('cache.web:' . self::$data->name, null);
		if(!$store)
		{
			$store = config('cache.web');
		}

		if($store)
		{
			return $cache->store($store);
		}
		else
		{
			return $cache;
		}
	}

	protected function childPage(string $slug, $payload = null)
	{
		/** @var Model $model */

		$locale = app()->getLocale();
		$model = app(self::$data->model_name);
		$cache = $this->cache("child");
		$cache_key = null;
		$view = self::$data->template . ".child";

		if($cache)
		{
			$cache_key = $model->getTable() . '/' . $locale . '/child:' . $slug;
			if($cache->has($cache_key))
			{
				return $this->view($view, (array) $cache->get($cache_key));
			}
		}

		$model = $this->prepareChildRequest($model, $slug, request(), $payload)->first();
		if(!$model)
		{
			return $this->abort404();
		}
		else
		{
			$data = $this->prepareView("child", [
				"type" => "child",
				"view" => $view,
				"name" => self::$data->name,
				"model" => $model,
				"locale" => $locale,
				"dataRoute" => $this->getDataRoute(),
			], $payload);

			if($cache)
			{
				$cache->set($cache_key, $data, $this->cache_ttl);
			}

			return $this->view($view, $data);
		}
	}

	protected function indexPage($payload = null)
	{
		// clear pagination data
		$this->pagination_page  = 1;
		$this->pagination_pages = 1;
		$this->pagination_total = 0;

		/** @var Model $model */

		$locale = app()->getLocale();
		$model = app(self::$data->model_name);
		$cache = $this->cache("index");
		$cache_key = null;
		$view = self::$data->template . ".index";

		if($cache)
		{
			$cache_key = $model->getTable() . '/' . $locale . '/index';
			if($cache->has($cache_key))
			{
				return $this->view($view, (array) $cache->get($cache_key));
			}
		}

		$items = $this->prepareIndexRequest($model, request(), $payload)->get();
		$paginate = null;

		if($this->pagination)
		{
			$paginate = (object) [
				"page"  => $this->pagination_page,
				"pages" => $this->pagination_pages,
				"total" => $this->pagination_total,
				"count" => $items->count(),
				"limit" => $this->pagination_limit,
			];
		}

		$data = $this->prepareView("index", [
			"type" => "index",
			"view" => $view,
			"name" => self::$data->name,
			"items" => $items,
			"locale" => $locale,
			"paginate" => $paginate,
			"count" => $items->count(),
			"dataRoute" => $this->getDataRoute(),
		], $payload);

		if($cache)
		{
			$cache->set($cache_key, $data, $this->cache_ttl);
		}

		return $this->view($view, $data);
	}

	protected function abort404($message = '', array $headers = [])
	{
		return $this->abort(404, $message, $headers);
	}

	protected function abort($code, $message = '', array $headers = [])
	{
		return abort($code, $message, $headers);
	}

	protected function getDataRoute()
	{
		return DataRoute::find(self::$data->key);
	}

	/**
	 * @param Model | Illuminate\Database\Eloquent\Builder $model
	 * @param Request $request
	 * @param mixed $payload
	 * @return Model | Illuminate\Database\Eloquent\Builder
	 */
	protected function prepareIndexRequest($model, Request $request, $payload = null)
	{
		$builder = $model;
		if($this->sort_field_name)
		{
			$builder = $builder->orderBy($this->sort_field_name, $this->sort_field_direction);
		}
		else if($this->sort_field_name !== false && $model instanceof Model && $model->usesTimestamps())
		{
			$builder = $builder->orderBy($model->getUpdatedAtColumn(), "desc");
		}

		$builder = $this->whereBuilder($builder);
		if($this->pagination)
		{
			$this->pagination_total = (int) (clone $builder)->reorder()->count();
			$this->pagination_pages = $this->pagination_total > $this->pagination_limit
				? ceil($this->pagination_total / $this->pagination_limit)
				: 1;

			$builder = $builder->limit($this->pagination_limit);
			$page = $request->getMethod($this->pagination_request_name);
			$page = is_numeric($page) && $page > 1 ? intval($page) : 1;
			if($page > $this->pagination_pages)
			{
				$page = $this->pagination_pages;
			}

			$this->pagination_page = $page;
			if($page > 1)
			{
				$builder = $builder->offset(
					$this->pagination_limit * ($page - 1)
				);
			}
		}

		return $builder;
	}

	/**
	 * @param Model | Illuminate\Database\Eloquent\Builder $model
	 * @param string  $slug
	 * @param Request $request
	 * @param mixed $payload
	 * @return Model | Illuminate\Database\Eloquent\Builder
	 */
	protected function prepareChildRequest($model, string $slug, Request $request, $payload = null)
	{
		$builder = $this->whereBuilder($model);
		return $builder->where(self::$data->slug_field, $slug);
	}

	/**
	 * @param string $type
	 * @param array  $data
	 * @param mixed  $payload
	 * @return array
	 */
	protected function prepareView(string $type, array $data, $payload = null)
	{
		return $data;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function jsonable(array $data): array
	{
		return $data;
	}

	protected function view($view, array $data)
	{
		if($this->json_mode)
		{
			$request = \request();
			if($request->expectsJson())
			{
				return $this->jsonable($data);
			}
		}

		return view($view, $data);
	}

	/**
	 * @param @param Model | Illuminate\Database\Eloquent\Builder $builder
	 * @return Model | Illuminate\Database\Eloquent\Builder
	 */
	private function whereBuilder($builder)
	{
		$where = $this->where;
		if(is_array($where))
		{
			return $builder->where($this->where);
		}
		if($where instanceof \Closure)
		{
			return $where($builder);
		}
		return $builder;
	}
}