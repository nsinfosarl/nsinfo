<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023-2024	Nicolas SILOBRE 		<nsilobre@ns-info.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   nsinfo     Module Nsinfo
 *  \brief      Nsinfo module descriptor.
 *
 *  \file       htdocs/nsinfo/core/modules/modNsinfo.class.php
 *  \ingroup    nsinfo
 *  \brief      Description and activation file for module Nsinfo
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Nsinfo
 */
class modNsinfo extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;
		$this->numero = 172760;
		$this->rights_class = 'nsinfo';
		$this->family = "NS INFO";
		$this->module_position = '01';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "NsinfoDescription";
		$this->descriptionlong = "Nsinfo description (Long)";
		$this->editor_name = 'NS INFO';
		$this->editor_url = 'https://www.ns-info.fr';
		$this->version = trim(file_get_contents(__DIR__.'/../../VERSION'));
        $this->url_last_version = 'https://www.ns-info.fr/dolibarr/ver.php?m=nsinfo';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'nsinfo@nsinfo';

		$this->module_parts = array(
			'css' => array('/nsinfo/css/nsinfo.css.php')
		);
		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/nsinfo/temp","/nsinfo/subdir");
		$this->dirs = array("/nsinfo/temp");
		// Config pages. Put here list of php page, stored into nsinfo/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@nsinfo");
		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("nsinfo@nsinfo");
		$this->phpmin = array(5, 5); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'NsinfoWasAutomaticallyActivatedBecauseOfYourCountryChoice');


		$this->const = array(
            1 => array('NSINFO_VERSIONMODULE', 'varchar', 1, $langs->trans('NSINFO_VERSIONMODULE'), 0)
        );

		if (!isset($conf->nsinfo) || !isset($conf->nsinfo->enabled)) {
			$conf->nsinfo = new stdClass();
			$conf->nsinfo->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		// Add here list of php file(s) stored in nsinfo/core/boxes that contains a class to show a widget.
		$this->boxes = array();

		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;

		// Main menu entries to add
		$this->menu = array();
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/nsinfo/sql/');
		if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
