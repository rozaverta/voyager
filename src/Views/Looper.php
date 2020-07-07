<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.07.2020
 * Time: 12:43
 */

namespace TCG\Voyager\Views;

class Looper
{
	/**
	 * @var null | TabManager
	 */
	private $tabManager = null;

	/**
	 * @var null | Tab
	 */
	private $tab = null;

	/**
	 * @var null | Board
	 */
	private $board = null;

	/**
	 * @var bool 
	 */
	private $aborted = false;

	/**
	 * @var int
	 */
	private $index = 0;
	
	public function aborted()
	{
		return $this->aborted;
	}
	
	public function abort()
	{
		$this->aborted = true;
	}

	public function fire(\Closure $closure, ... $args)
	{
		if(!$this->aborted())
		{
			$args[] = $this;
			$closure(... $args);
			$this->index ++;
		}
	}

	/**
	 * @return int
	 */
	public function getIndex(): int
	{
		return $this->index;
	}

	/**
	 * @param Board|null $board
	 * @return $this
	 */
	public function setBoard( Board $board )
	{
		$this->board = $board;
		return $this;
	}

	/**
	 * @return Board|null
	 */
	public function getBoard(): ?Board
	{
		return $this->board;
	}

	/**
	 * @param TabManager|null $manager
	 * @return $this
	 */
	public function setTabManager( TabManager $manager )
	{
		$this->tabManager = $manager;
		return $this;
	}

	/**
	 * @return TabManager|null
	 */
	public function getTabManager(): ?TabManager
	{
		return $this->tabManager;
	}

	/**
	 * @param Tab|null $tab
	 * @return $this
	 */
	public function setTab( Tab $tab )
	{
		$this->tab = $tab;
		return $this;
	}

	/**
	 * @return Tab|null
	 */
	public function getTab(): ?Tab
	{
		return $this->tab;
	}
}