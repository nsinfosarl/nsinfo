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
 *  \brief      Fichier de description et d'activation du module Nsinfo
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Classe de description et d'activation du module Nsinfo
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
			'css' => array(
                '/nsinfo/css/nsinfo.css.php',
                '/nsinfo/css/nsfa.css.php',
                getDolGlobalInt('NSINFO_CSSSTATUS')?'/nsinfo/css/nsinfostatus.css.php':'',
                ),
			// Indiquer le chemin relatif du fichier js si le module doit charger un js sur toutes les pages
			'js' => array(
				'/nsinfo/js/nsinfo.js',
				'/nsinfo/js/modules/toolbox.js',
				'/nsinfo/js/modules/loader.js',
				'/nsinfo/js/modules/modal.js',
				'/nsinfo/js/modules/mediaGallery.js',
				'/nsinfo/js/modules/menu.js',	// Réduction du menu de gauche (nsinfoMenuMinimize)
			)
		);
		// Répertoires de données à créer à l'activation du module.
		// Exemple : this->dirs = array("/nsinfo/temp","/nsinfo/subdir");
		$this->dirs = array("/nsinfo/temp");
		// Pages de configuration. Indiquer ici la liste des pages php, stockées dans le répertoire nsinfo/admin, servant à paramétrer le module.
		$this->config_page_url = array("setup.php@nsinfo");
		// Dependencies
		// Une condition pour masquer le module
		$this->hidden = false;
		// Liste des noms de classes de modules (chaînes) à activer si ce module est activé. Exemple : array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // Liste des noms de classes de modules (chaînes) à désactiver si celui-ci est désactivé. Exemple : array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // Liste des noms de classes de modules (chaînes) en conflit avec ce module. Exemple : array('modModuleToDisable1', ...)
		$this->langfiles = array("nsinfo@nsinfo");
		$this->phpmin = array(5, 5); // Version minimale de PHP requise par le module
		$this->need_dolibarr_version = array(11, -3); // Version minimale de Dolibarr requise par le module
		$this->warnings_activation = array(); // Avertissement à afficher lors de l'activation du module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Avertissement à afficher lors de l'activation d'un module externe. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'NsinfoWasAutomaticallyActivatedBecauseOfYourCountryChoice');


		$this->const = array(
            1 => array('NSINFO_VERSIONMODULE', 'varchar', 1, $langs->trans('NSINFO_VERSIONMODULE'), 0)
        );

		if (!isset($conf->nsinfo) || !isset($conf->nsinfo->enabled)) {
			$conf->nsinfo = new stdClass();
			$conf->nsinfo->enabled = 0;
		}

		// Tableau pour ajouter de nouvelles pages dans de nouveaux onglets
		$this->tabs = array();

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		// Indiquer ici la liste des fichiers php stockés dans nsinfo/core/boxes qui contiennent une classe affichant un widget.
		$this->boxes = array();

		$this->cronjobs = array();

		// Droits fournis par ce module
		$this->rights = array();
		$r = 0;

		// Entrées de menu principal à ajouter
		$this->menu = array();
	}

	/**
	 *  Fonction appelée à l'activation du module.
	 *  La fonction init ajoute dans la base Dolibarr les constantes, boîtes, droits et menus (définis dans le constructeur).
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options lors de l'activation du module ('', 'noboxes')
	 *  @return     int             	1 si OK, 0 si KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/nsinfo/sql/');
		if ($result < 0) return -1; // Ne pas activer le module si l'erreur 'not allowed' est renvoyée lors du chargement des requêtes SQL du module (_load_table exécute run_sql avec le paramètre d'erreur autorisée réglé sur 'default')

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *  Fonction appelée à la désactivation du module.
	 *  Supprime de la base Dolibarr les constantes, boîtes et droits.
	 *  Les répertoires de données ne sont pas supprimés
	 *
	 *  @param      string	$options    Options lors de la désactivation du module ('', 'noboxes')
	 *  @return     int                 1 si OK, 0 si KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
