<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2020
 * Time: 16:03
 */

namespace TCG\Voyager\Views;

class Tab implements \Countable
{
	/**
	 * @var Board[]
	 */
	protected $boards = [];

	protected $default = null;

	protected $name;

	protected $label;

	public function __construct(string $name, string $label)
	{
		$this->name = $name;
		$this->label = $label;
	}

	public function count()
	{
		return count($this->boards);
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

		$loop->setTab($this);
		foreach($this->boards as $board)
		{
			if($loop->aborted())
			{
				break;
			}
			$board->each($closure, $loop);
		}

		return $this;
	}

	public function filled(): bool
	{
		foreach($this->boards as $board)
		{
			if($board->count())
			{
				return true;
			}
		}
		return false;
	}

	public function isBaseBoards(): bool
	{
		foreach($this->boards as $board)
		{
			if(!$board->isAside() && $board->count())
			{
				return true;
			}
		}
		return false;
	}

	public function isAside(): bool
	{
		foreach($this->boards as $board)
		{
			if($board->isAside() && $board->count())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLabel(): string
	{
		return $this->label;
	}

	/**
	 * @param string $label
	 */
	public function setLabel( string $label ): void
	{
		$this->label = $label;
	}

	/**
	 * @return string|null
	 */
	public function getDefaultName(): ?string
	{
		return $this->default;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setDefaultName(string $name)
	{
		if(isset($this->boards[$name]))
		{
			$this->default = $name;
		}
		return $this;
	}

	/**
	 * @param Board $board
	 * @return $this
	 */
	public function setDefaultBoard(Board $board)
	{
		return $this->setDefaultName($board->getName());
	}

	public function getDefaultBoard(): ?Board
	{
		return $this->default == null ? null : $this->boards[$this->default];
	}

	/**
	 * @param Board $board
	 * @return bool
	 */
	public function hasDefaultBoard(Board $board): bool
	{
		return $this->default === $board->getName();
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasBoardName(string $name): bool
	{
		return isset($this->boards[$name]);
	}

	/**
	 * @param Board $board
	 * @return bool
	 */
	public function hasBoard(Board $board): bool
	{
		return $this->hasBoardName($board->getName());
	}

	/**
	 * @param Board $board
	 * @return $this
	 */
	public function addBoard(Board $board)
	{
		$name = $board->getName();
		if(isset($this->boards[$name]))
		{
			throw new \InvalidArgumentException("Duplicate board name '{$name}' for the {$this->name} tab.");
		}

		$this->boards[$name] = $board;
		if(!$this->default)
		{
			$this->default = $name;
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return Board|null
	 */
	public function getBoard(string $name): ?Board
	{
		return $this->boards[$name] ?? null;
	}

	/**
	 * Remove empty boards
	 *
	 * @return $this
	 */
	public function refill()
	{
		$this->boards = $this->getBoards();
		return $this;
	}

	/**
	 * @return Board[]
	 */
	public function getAllBoards(): array
	{
		return array_values($this->boards);
	}

	/**
	 * Get clean boards
	 *
	 * @return Board[]
	 */
	public function getBoards(): array
	{
		return array_filter($this->getAllBoards(), function(Board $board) {
			return $board->count() > 0;
		});
	}

	/**
	 * Get clean base boards
	 *
	 * @return Board[]
	 */
	public function getBaseBoards(): array
	{
		return array_filter($this->getBoards(), function(Board $board) {
			return ! $board->isAside();
		});
	}

	/**
	 * Get clean aside boards
	 *
	 * @return Board[]
	 */
	public function getAsideBoards(): array
	{
		return array_filter($this->getBoards(), function(Board $board) {
			return $board->isAside();
		});
	}
}