<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *  \file       htdocs/core/modules/nsinfo/mod_myobject_standard.php
 *  \ingroup    nsinfo
 *  \brief      Fichier de classe de gestion des règles de numérotation standard de MyObject
 */
dol_include_once('/nsinfo/core/modules/nsinfo/modules_myobject.php');


/**
 *	Classe de gestion des règles de numérotation standard de MyObject
 */
class mod_myobject_standard extends ModeleNumRefMyObject
{
	/**
	 * Version de Dolibarr du document chargé
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefix = 'MYOBJECT';

	/**
	 * @var string Code d'erreur (ou message)
	 */
	public $error = '';

	/**
	 * @var string nom
	 */
	public $name = 'standard';


	/**
	 *  Renvoie la description du module de numérotation
	 *
	 *  @return     string      Texte descriptif
	 */
	public function info()
	{
		global $langs;
		return $langs->trans("SimpleNumRefModelDesc", $this->prefix);
	}


	/**
	 *  Renvoie un exemple de numérotation
	 *
	 *  @return     string      Exemple
	 */
	public function getExample()
	{
		return $this->prefix."0501-0001";
	}


	/**
	 *  Vérifie que les numéros déjà présents en base ne
	 *  provoquent pas de conflits qui empêcheraient cette numérotation de fonctionner.
	 *
	 *  @param  Object		$object		Objet pour lequel on a besoin de la valeur suivante
	 *  @return boolean     			false en cas de conflit, true si OK
	 */
	public function canBeActivated($object)
	{
		global $conf, $langs, $db;

		$coyymm = ''; $max = '';

		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."nsinfo_myobject";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = ".$conf->entity;
		}
		elseif ($object->ismultientitymanaged == 2) {
			// TODO
		}

		$resql = $db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $coyymm = substr($row[0], 0, 6); $max = $row[0]; }
		}
		if ($coyymm && !preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i', $coyymm))
		{
			$langs->load("errors");
			$this->error = $langs->trans('ErrorNumRefModel', $max);
			return false;
		}

		return true;
	}

	/**
	 * 	Renvoie la prochaine valeur libre
	 *
	 *  @param  Object		$object		Objet pour lequel on a besoin de la valeur suivante
	 *  @return string      			Valeur si OK, <0 si KO
	 */
	public function getNextValue($object)
	{
		global $db, $conf;

		// on récupère d'abord la valeur maximale
		$posindice = strlen($this->prefix) + 6;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."nsinfo_myobject";
		$sql .= " WHERE ref LIKE '".$db->escape($this->prefix)."____-%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = ".$conf->entity;
		}
		elseif ($object->ismultientitymanaged == 2) {
			// TODO
		}

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max = 0;
		}
		else
		{
			dol_syslog("mod_myobject_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		//$date=time();
		$date = $object->date_creation;
		$yymm = strftime("%y%m", $date);

		if ($max >= (pow(10, 4) - 1)) $num = $max + 1; // Si le compteur dépasse 9999, on ne formate pas sur 4 caractères, on prend le nombre tel quel
		else $num = sprintf("%04s", $max + 1);

		dol_syslog("mod_myobject_standard::getNextValue return ".$this->prefix.$yymm."-".$num);
		return $this->prefix.$yymm."-".$num;
	}
}
