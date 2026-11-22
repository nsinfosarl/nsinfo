<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2012      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file			htdocs/core/modules/nsinfo/modules_myobject.php
 *  \ingroup		nsinfo
 *  \brief			Fichier contenant la classe mère des modèles de documents et la classe mère des modèles de numérotation des myobjects
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php'; // nécessaire aux classes qui en héritent


/**
 *	Classe mère des modèles de documents
 */
abstract class ModelePDFMyObject extends CommonDocGenerator
{

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoie la liste des modules de génération actifs
	 *
	 *  @param	DoliDB	$db     			Gestionnaire de base de données
	 *  @param  integer	$maxfilenamelength  Longueur maximale de la valeur à afficher
	 *  @return	array						Liste des modèles
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'myobject';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}
}



/**
 *  Classe mère de gestion de la numérotation de MyObject
 */
abstract class ModeleNumRefMyObject
{
	/**
	 * @var string Code d'erreur (ou message)
	 */
	public $error = '';

	/**
	 *	Indique si un module peut être utilisé ou non
	 *
	 *	@return		boolean     true si le module peut être utilisé
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *	Renvoie la description par défaut du modèle de numérotation
	 *
	 *	@return     string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("nsinfo@nsinfo");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Renvoie un exemple de numérotation
	 *
	 *	@return     string      Exemple
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("nsinfo@nsinfo");
		return $langs->trans("NoExample");
	}

	/**
	 *  Vérifie que les numéros déjà présents en base ne
	 *  provoquent pas de conflits qui empêcheraient cette numérotation de fonctionner.
	 *
	 *	@param	Object		$object		Objet pour lequel on a besoin de la valeur suivante
	 *	@return boolean     			false en cas de conflit, true si OK
	 */
	public function canBeActivated($object)
	{
		return true;
	}

	/**
	 *	Renvoie la prochaine valeur attribuée
	 *
	 *	@param	Object		$object		Objet pour lequel on a besoin de la valeur suivante
	 *	@return	string      Valeur
	 */
	public function getNextValue($object)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Renvoie la version du module de numérotation
	 *
	 *	@return     string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
