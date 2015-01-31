<?php

/*
	WildPHP - a modular and easily extendable IRC bot written in PHP
	Copyright (C) 2015 WildPHP

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WildPHP\Core;

class ModuleManager
{
	private $module_dir;
	protected $modules = array();

	/**
	 * The Bot object. Used to interact with the main thread.
	 * @var object
	 */
	protected $bot;

	public function __construct($bot, $dir = WPHP_MODULE_DIR)
	{
		$this->module_dir = $dir;
		$this->bot = $bot;

		// Register our autoloader.
		spl_autoload_register(array($this, 'autoLoad'));

		// Scan the modules directory for any available modules
		foreach (scandir($this->module_dir) as $file)
		{
			if (is_dir($this->module_dir . $file) && $file != '.' && $file != '..')
			{
				$this->modules[] = $file;
				$this->loadModule($file);
			}
		}
	}

	// Load a module. Resolve its dependencies. Recurse over dependencies
	public function loadModule($module)
	{
		$module_full = 'WildPHP\\modules\\' . $module;

		if (class_exists($module_full))
		{
			$this->modules[$module] = new $module_full($this->bot);
			$this->bot->log('Module ' . $module . ' loaded.', 'MODMGR');
		}
	}

	// Reverse the loading of the module.
	public function unloadModule($module)
	{
		if (!empty($this->modules[$module]))
			unset($this->modules[$module]);
	}

	// The autoloader for modules
	public function autoLoad($class)
	{
		$class = str_replace('WildPHP\\modules\\', '', $class);
		require_once($this->module_dir . $class . '/' . $class . '.php');
	}
}
