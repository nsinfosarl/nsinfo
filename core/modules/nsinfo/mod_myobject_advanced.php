<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019       Frédéric France             <frederic.france@netlogic.fr>
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
 * \file       htdocs/core/modules/nsinfo/mod_myobject_advanced.php
 * \ingroup    nsinfo
 * \brief      Fichier contenant la classe du modèle de numérotation avancé de MyObject
 */

dol_include_once('/nsinfo/core/modules/nsinfo/modules_myobject.php');


/**
 *	Classe de gestion des règles de numérotation avancées de MyObject
 */
class mod_myobject_advanced extends ModeleNumRefMyObject
{
	/**
	 * Version de Dolibarr du document chargé
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Message d'erreur
	 */
	public $error = '';

	/**
	 * @var string nom
	 */
	public $name = 'advanced';


	/**
	 *  Renvoie la description du modèle de numérotation
	 *
	 *  @return     string      Texte descripif
	 */
	public function info()
	{
		global $conf, $langs, $db;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="updateMask">';
		$texte .= '<input type="hidden" name="maskconstBom" value="NSINFO_MYOBJECT_ADVANCED_MASK">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("MyObject"), $langs->transnoentities("MyObject"));
		$tooltip .= $langs->trans("GenericMaskCodes2");
		$tooltip .= $langs->trans("GenericMaskCodes3");
		$tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("MyObject"), $langs->transnoentities("MyObject"));
		$tooltip .= $langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte .= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskMyObject" value="'.getDolGlobalString('NSINFO_MYOBJECT_ADVANCED_MASK').'">', $tooltip, 1, 1).'</td>';

		$texte .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *  Renvoie un exemple de numérotation
	 *
	 *  @return     string      Exemple
	 */
	public function getExample()
	{
		global $conf, $db, $langs, $mysoc;

		$object = new MyObject($db);
		$object->initAsSpecimen();

		/*$old_code_client = $mysoc->code_client;
		$old_code_type = $mysoc->typent_code;
		$mysoc->code_client = 'CCCCCCCCCC';
		$mysoc->typent_code = 'TTTTTTTTTT';*/

		$numExample = $this->getNextValue($object);

		/*$mysoc->code_client = $old_code_client;
		$mysoc->typent_code = $old_code_type;*/

		if (!$numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
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

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// On récupère la règle du curseur
		$mask = getDolGlobalString('NSINFO_MYOBJECT_ADVANCED_MASK');

		if (!$mask)
		{
			$this->error = 'NotConfigured';
			return 0;
		}

		$date = $object->date;

		$numFinal = get_next_value($db, $mask, 'nsinfo_myobject', 'ref', '', null, $date);

		return  $numFinal;
	}
}
