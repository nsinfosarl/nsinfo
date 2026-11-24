<?php
/* Copyright (C) 2026 NS INFO <contact@ns-info.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    nsinfo/class/modhistory.class.php
 * \ingroup nsinfo
 * \brief   Historique des modules NS INFO (table llx_nsinfo_modhistory) : dates de dernière installation, de dernière mise à jour
 *          et de dernière réactivation de chaque module
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Classe Modhistory : une ligne par module NS INFO et par entité
 */
class Modhistory extends CommonObject
{
	/**
	 * @var string ID du module
	 */
	public $module = 'nsinfo';

	/**
	 * @var string ID de l'élément
	 */
	public $element = 'modhistory';

	/**
	 * @var string Nom de la table sans préfixe
	 */
	public $table_element = 'nsinfo_modhistory';

	/**
	 * @var int 1 si l'objet est multi-société (champ entity)
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int 0 : aucun champ supplémentaire
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string Picto de l'objet
	 */
	public $picto = 'object_nsinfo@nsinfo';

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array<string,mixed>> Champs (format ModuleBuilder)
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'css' => 'left', 'comment' => 'Id'),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 5, 'notnull' => 1, 'visible' => 0, 'default' => '1', 'index' => 1),
		'modname' => array('type' => 'varchar(64)', 'label' => 'Module', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'comment' => 'Classe du descripteur (modXxx)'),
		'version' => array('type' => 'varchar(32)', 'label' => 'Version', 'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => 1, 'comment' => 'Dernière version vue'),
		'date_install' => array('type' => 'datetime', 'label' => 'NsinfoModsDateInstall', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 1),
		'date_update' => array('type' => 'datetime', 'label' => 'NsinfoModsDateUpdate', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => 1),
		'date_reload' => array('type' => 'datetime', 'label' => 'NsinfoModsDateReload', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 1),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => '0'),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'picto' => 'user', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2),
	);

	/**
	 * @var int Rowid
	 */
	public $rowid;
	/**
	 * @var int Entité
	 */
	public $entity;
	/**
	 * @var string Classe du descripteur du module (modXxx)
	 */
	public $modname;
	/**
	 * @var string Dernière version vue
	 */
	public $version;
	/**
	 * @var int|string Date de dernière installation (activation)
	 */
	public $date_install;
	/**
	 * @var int|string Date de dernière mise à jour (changement de version)
	 */
	public $date_update;
	/**
	 * @var int|string Date de dernière réactivation (rechargement)
	 */
	public $date_reload;
	/**
	 * @var int Utilisateur créateur
	 */
	public $fk_user_creat;
	/**
	 * @var int Utilisateur modificateur
	 */
	public $fk_user_modif;
	// END MODULEBUILDER PROPERTIES

	/**
	 * Constructeur
	 *
	 * @param	DoliDB	$db		Gestionnaire de base de données
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Historique de tous les modules de l'entité courante
	 *
	 * @param	DoliDB	$db		Gestionnaire de base de données
	 * @return	array<string,array{version:string,date_install:int|string,date_update:int|string,date_reload:int|string}>	Classe du module => dates (horodatages)
	 */
	public static function getAll($db)
	{
		global $conf;

		$list = array();
		$resql = $db->query("SELECT modname, version, date_install, date_update, date_reload FROM ".$db->prefix()."nsinfo_modhistory WHERE entity = ".((int) $conf->entity));
		while ($resql && ($obj = $db->fetch_object($resql))) {
			$list[$obj->modname] = array(
				'version' => (string) $obj->version,
				'date_install' => $db->jdate($obj->date_install),
				'date_update' => $db->jdate($obj->date_update),
				'date_reload' => $db->jdate($obj->date_reload),
			);
		}
		return $list;
	}

	/**
	 * Enregistre les valeurs d'un module (ligne créée si absente)
	 *
	 * @param	DoliDB					$db		Gestionnaire de base de données
	 * @param	User					$author	Utilisateur qui fait la modification
	 * @param	string					$module	Classe du descripteur (modXxx)
	 * @param	array<string,mixed>		$values	Champs à écrire : version (chaîne), date_install / date_update / date_reload (horodatage ou '')
	 * @return	int								>0 si OK, <0 si KO
	 */
	public static function set($db, $author, $module, $values)
	{
		global $conf;

		$set = array();
		foreach ($values as $field => $value) {
			if ($field == 'version') {
				$set[$field] = "'".$db->escape((string) $value)."'";
			} elseif (in_array($field, array('date_install', 'date_update', 'date_reload'), true)) {
				$set[$field] = $value ? "'".$db->idate($value)."'" : 'NULL';
			}
		}
		if (empty($set)) {
			return 0;
		}
		$resql = $db->query("SELECT rowid FROM ".$db->prefix()."nsinfo_modhistory WHERE entity = ".((int) $conf->entity)." AND modname = '".$db->escape($module)."'");
		if (!$resql) {
			return -1;
		}
		if ($obj = $db->fetch_object($resql)) {
			$parts = array();
			foreach ($set as $field => $value) {
				$parts[] = $field." = ".$value;
			}
			$sql = "UPDATE ".$db->prefix()."nsinfo_modhistory SET ".implode(', ', $parts).", fk_user_modif = ".((int) $author->id)." WHERE rowid = ".((int) $obj->rowid);
		} else {
			$sql = "INSERT INTO ".$db->prefix()."nsinfo_modhistory(entity, modname, ".implode(', ', array_keys($set)).", date_creation, fk_user_creat)";
			$sql .= " VALUES (".((int) $conf->entity).", '".$db->escape($module)."', ".implode(', ', $set).", '".$db->idate(dol_now())."', ".((int) $author->id).")";
		}
		return $db->query($sql) ? 1 : -1;
	}
}
