<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2020
 * Time: 16:48
 */

namespace TCG\Voyager\Views;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use TCG\Voyager\Models\DataType;

class BreadManager
{
	protected $model;

	protected $hidden;

	protected $tab_manager;

	protected $settings;

	public function __construct(Model $model, $settings = null)
	{
		$this->model = $model;
		$this->settings = empty($settings) ? new \stdClass() : $settings;
		$this->tab_manager = new TabManager();
		$this->hidden = new Board("hidden");
	}

	/**
	 * @return Model
	 */
	public function getModel(): Model
	{
		return $this->model;
	}

	/**
	 * @return \stdClass
	 */
	public function getSettings(): \stdClass
	{
		return $this->settings;
	}

	/**
	 * Load content rows
	 *
	 * @param DataType $dataType
	 */
	public function load(DataType $dataType)
	{
		$variant = $this->hidden;
		$manager = $this->tab_manager;
		$user_role = null;

		$store = (array) $this->settings;
		$settings = null;

		/**
		 * @var App\User $user | null
		 */
		$user = Auth::user();
		if($user)
		{
			$key = 'user:' . $user->id;
			$role = $user->role()->first();
			if($role)
			{
				$user_role = $role->name;
			}

			if(isset($store[$key]))
			{
				$settings = $store[$key];
			}
			else if($user_role)
			{
				$key = 'role:' . $user_role;
				if(isset($store[$key]))
				{
					$settings = $store[$key];
				}
			}
		}

		if(!$settings && isset($store["*"]))
		{
			$settings = $store["*"];
		}

		// create tabs
		if($settings && isset($settings->tabs) && is_array($settings->tabs))
		{
			foreach($settings->tabs as $tab)
			{
				$name = (string) $tab->name ?? "default";
				$newTab = new Tab($name, (string) $tab->label ?? Str::ucfirst($name));

				// fill boards
				if(isset($tab->boards) && is_array($tab->boards))
				{
					foreach($tab->boards as $board)
					{
						$name = (string) $board->name ?? "default";
						$newBoard = new Board(
							$name,
							isset($board->label) ? (string) $board->label : null,
							isset($board->collapse) ? (bool) $board->collapse : false,
							isset($board->aside) ? (bool) $board->aside : false
						);
						if(isset($board->icon))
						{
							$newBoard->setIcon((string) $board->icon);
						}
						$newTab->addBoard($newBoard);
					}
				}

				// create default, if empty and set default
				if(!$newTab->count())
				{
					$newTab->addBoard(new Board("default"));
				}
				else if(isset($tab->defaultBoard))
				{
					$newTab->setDefaultName((string) $tab->defaultBoard);
				}

				$manager->addTab($newTab);
			}
		}

		// default tab (one)
		if(!$manager->count())
		{
			$tab = new Tab("default", "Content");
			$tab->addBoard(new Board("default"));
			$manager->addTab($tab);
		}

		$rows = is_null($this->model->getKey()) ? $dataType->addRows : $dataType->editRows;

		// fill content
		foreach($rows as $row)
		{
			$display = $row->details->display ?? new \stdClass();
			if(isset($display->rule))
			{
				// show
				if( isset($display->rule->show) )
				{
					$variant = (array) $display->rule->show;
					if( !in_array($user_role, $variant, true))
					{
						continue;
					}
				}

				// hidden
				else if( isset($display->rule->hidden) )
				{
					$variant = (array) $display->rule->hidden;
					if( in_array($user_role, $variant, true))
					{
						continue;
					}
				}
			}

			// hidden ?
			if($row->type == 'hidden')
			{
				$this->addHiddenItem($row);
			}

			// to tab
			else
			{
				$tab = $manager->getTab(
					isset($display->tab) && $manager->hasTabName($display->tab)
						? $display->tab
						: null
				);

				$tab
					->getBoard(
						isset($display->board) && $tab->hasBoardName($display->board)
							? $display->board
							: $tab->getDefaultName()
					)
					->addItem($row);
			}
		}

		event('voyager.admin.display-settings.bread', $this);

		$manager->refill();
		return $this;
	}

	/**
	 * @return int
	 */
	public function hiddenCount()
	{
		return $this->hidden->count();
	}

	/**
	 * @param $item
	 * @return $this
	 */
	public function addHiddenItem($item)
	{
		$this->hidden->addItem($item);
		return $this;
	}

	/**
	 * @param int $number
	 * @return mixed|null
	 */
	public function getHiddenItem(int $number)
	{
		return $this->hidden->getItem($number);
	}

	/**
	 * @return array
	 */
	public function getHiddenItems(): array
	{
		return $this->hidden->getItems();
	}

	/**
	 * @return TabManager
	 */
	public function getTabManager(): TabManager
	{
		return $this->tab_manager;
	}
}