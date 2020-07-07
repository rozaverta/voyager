<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;
use Illuminate\Support\Facades\Cache;

/**
 * TCG\Voyager\Models\DataRoute
 *
 * @property int $id
 * @property int $data_type_id
 * @property string $name
 * @property string $title
 * @property string|null $sub_title
 * @property string|null $body
 * @property string $slug
 * @property string|null $slug_field
 * @property string $controller_name
 * @property string $template
 * @property int|null $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereDataTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereSubTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereSlugField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereControllerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\TCG\Voyager\Models\DataRoute whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DataRoute extends Model
{
	use Translatable;

	protected $translatable = ['title', 'sub_title', 'body'];

	protected $table = 'data_routes';

	protected $fillable = [
		'data_type_id',
		'name',
		'title',
		'sub_title',
		'body',
		'slug',
		'slug_field',
		'controller_name',
		'template',
		'order',
	];

	public function dataType()
	{
		return $this
			->belongsTo(DataType::class, "data_type_id");
	}

	public function save( array $options = [] )
	{
		Cache::forget('voyager.routers.web');

		// Insert auto order
		if (!$this->exists && empty($this->order))
		{
			$this->order = DataRoute::max('order') + 1;
		}

		// Slug format
		$this->slug = trim($this->slug ?? "", "/");
		if(!strlen($this->slug))
		{
			$this->slug = "/";
		}

		return parent::save( $options );
	}
}