<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2020
 * Time: 16:03
 */

namespace TCG\Voyager\Views;

use Illuminate\Support\Str;

class Board implements \Countable
{
	protected $name;

	protected $label = null;

	protected $collapse = false;

	protected $aside = false;

	protected $icon = null;

	protected $index = 0;

	protected $items = [];

	public function __construct(string $name, string $label = null, bool $collapse = false, bool $aside = false)
	{
		$this->name = $name;
		$this->label = $label;
		$this->collapse = $collapse;
		$this->aside = $aside;
	}

	public function count()
	{
		return count($this->items);
	}

	/**
	 * @return bool
	 */
	public function isAside(): bool
	{
		return $this->aside;
	}

	/**
	 * @param bool $aside
	 */
	public function setAside( bool $aside ): void
	{
		$this->aside = $aside;
	}

	/**
	 * @return bool
	 */
	public function isCollapse(): bool
	{
		return $this->collapse;
	}

	/**
	 * @param bool $collapse
	 */
	public function setCollapse( bool $collapse ): void
	{
		$this->collapse = $collapse;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string|null $label
	 */
	public function setLabel( ?string $label ): void
	{
		$this->label = $label;
	}

	/**
	 * @return string|null
	 */
	public function getLabel(): ?string
	{
		$label = $this->label;
		if($this->collapse && ! $label)
		{
			$label = Str::ucfirst($this->name);
		}
		return $label;
	}

	/**
	 * @return bool
	 */
	public function hasLabel(): bool
	{
		return $this->collapse || ! empty($this->label);
	}

	/**
	 * @return string|null
	 */
	public function getIcon(): ?string
	{
		return $this->icon;
	}

	/**
	 * @param null $icon
	 * @return $this
	 */
	public function setIcon( string $icon )
	{
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasIcon(): bool
	{
		return $this->icon != null;
	}

	/**
	 * Add new item component
	 *
	 * @param $item
	 * @return $this
	 */
	public function addItem($item)
	{
		$this->items[] = [$this->index ++, $item];
		return $this;
	}

	/**
	 * Get item component
	 *
	 * @param int $number
	 * @return mixed|null
	 */
	public function getItem(int $number)
	{
		$i = $this->itemIndex($number);
		return $i > -1 ? $this->items[$i][1] : null;
	}

	/**
	 * @param int $index
	 * @param     $item
	 * @return $this
	 */
	public function changeItem(int $index, $item)
	{
		$i = $this->itemIndex($number);
		if($i > -1)
		{
			$this->items[$i][1] = $item;
		}
		return $this;
	}

	/**
	 * @param int $index
	 * @return $this
	 */
	public function removeItem(int $index)
	{
		$i = $this->itemIndex($index);
		if($i > -1)
		{
			array_splice($this->items, $i, 1);
		}
		return $this;
	}

	/**
	 * @param \Closure    $closure
	 * @param Looper|null $loop
	 * @return $this
	 */
	public function each(\Closure $closure, Looper $loop = null)
	{
		if($loop == null)
		{
			$loop = new Looper();
		}

		$loop->setBoard($this);
		$items = $this->items;

		for($i = 0, $count = count($items); $i < $count; $i++)
		{
			if($loop->aborted())
			{
				break;
			}
			$item = $items[$i];
			$loop->fire($closure, $item[1], $item[0]);
		}

		return $this;
	}

	/**
	 * Get all items
	 *
	 * @return array
	 */
	public function getItems(): array
	{
		return array_map(function($item) { return $item[1]; }, $this->items);
	}

	/**
	 * @param int $index
	 * @return int
	 */
	protected function itemIndex(int $index)
	{
		for($i = 0, $count = count($this->items); $i < $count; $i++)
		{
			if($this->items[$i][0] === $index)
			{
				return $i;
			}
		}
		return -1;
	}
}