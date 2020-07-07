<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2020
 * Time: 16:03
 */

namespace TCG\Voyager\Views;

class TabManager implements \Countable
{
	/**
	 * @var Tab[]
	 */
	private $tabs = [];

	private $first_tab_name = null;

	public function count()
	{
		return count($this->tabs);
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

		$loop->setTabManager($this);
		foreach($this->tabs as $tab)
		{
			if($loop->aborted())
			{
				break;
			}
			$tab->each($closure, $loop);
		}
		return $this;
	}

	public function filled(): bool
	{
		foreach($this->tabs as $tab)
		{
			if($tab->filled())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return Tab[]
	 */
	public function getAllTabs(): array
	{
		return array_values($this->tabs);
	}

	/**
	 * @return Tab[]
	 */
	public function getTabs()
	{
		return array_filter($this->getAllTabs(), function(Tab $tab) {
			return $tab->filled();
		});
	}

	/**
	 * Add new tab
	 *
	 * @param Tab $tab
	 * @return $this
	 */
	public function addTab(Tab $tab)
	{
		$name = $tab->getName();
		if(isset($this->tabs[$name]))
		{
			throw new \InvalidArgumentException("Duplicate tab name '{$name}'.");
		}

		$this->tabs[$name] = $tab;
		if(!$this->first_tab_name)
		{
			$this->first_tab_name = $name;
		}

		return $this;
	}

	/**
	 * Get tab
	 *
	 * @param string|null $name
	 * @return Tab|null
	 */
	public function getTab(string $name = null): ?Tab
	{
		if($name == null)
		{
			$name = $this->first_tab_name;
		}
		return  $this->tabs[$name] ?? null;
	}

	/**
	 * @param Tab $tab
	 * @return bool
	 */
	public function hasTab(Tab $tab): bool
	{
		return $this->hasTabName($tab->getName());
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasTabName(string $name): bool
	{
		return isset($this->tabs[$name]);
	}

	/**
	 * Remove empty tabs
	 *
	 * @return $this
	 */
	public function refill()
	{
		$this->tabs = $this->getTabs();
		return $this;
	}
}