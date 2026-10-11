<?php
/* Copyright (C) 2022-2024 Nicolas SILOBRE <contact@ns-info.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    nsinfo/lib/nsinfo.lib.php
 * \ingroup nsinfo
 * \brief   Library files with common functions for Nsinfo
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function nsinfoAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("nsinfo@nsinfo");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/nsinfo/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cogs paddingright pictofixedwidth valignmiddle"></i>' . $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
    $head[$h][0] = dol_buildpath("/nsinfo/admin/changelog_module.php?namemodule=nsinfo", 1);
    $head[$h][1] = '<i class="fas fa-code-branch paddingright pictofixedwidth valignmiddle"></i>' . $langs->trans("NSINFOChangeLog");
    $head[$h][2] = 'changelog';
    $h++;


    $head[$h][0] = dol_buildpath("/nsinfo/admin/about_module.php?namemodule=nsinfo", 1);
    $head[$h][1] = '<i class="fab fa-readme paddingright pictofixedwidth valignmiddle"></i>' . $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'nsinfo');

	return $head;
}



/**
 * Entrée de menu de gauche « Réduire le menu » (même technique que le module Saturne) : un clic réduit le menu
 * de gauche à ses icônes ou le rétablit ; le choix est mémorisé dans le navigateur (js/modules/menu.js).
 * À ajouter en dernière position des menus de gauche d'un module, dans le constructeur du descripteur :
 *     dol_include_once('/nsinfo/lib/nsinfo.lib.php');
 *     if (function_exists('nsinfoMenuMinimize')) $this->menu[$r++] = nsinfoMenuMinimize('monmodule', 2000 + $r);
 *
 * @param	string	$mainmenu	Code du menu principal du module (ex : 'gmao')
 * @param	int		$position	Position de l'entrée (à placer après les autres entrées)
 * @param	string	$enabled	Condition d'affichage (dol_eval)
 * @return	array<string,mixed>	Entrée de menu pour $this->menu
 */
function nsinfoMenuMinimize($mainmenu, $position, $enabled = '1')
{
	global $langs;

	if (is_object($langs)) {
		$langs->load('nsinfo@nsinfo');
		$titleminimize = $langs->transnoentities('NSINFO_MinimizeMenu');
		$titlemaximize = $langs->transnoentities('NSINFO_MaximizeMenu');
	} else {
		$titleminimize = 'NSINFO_MinimizeMenu';
		$titlemaximize = 'NSINFO_MaximizeMenu';
	}

	return array(
		'fk_menu' => 'fk_mainmenu=' . $mainmenu,
		'type' => 'left',
		'titre' => 'NSINFO_MinimizeMenu',
		'prefix' => '<span class="fas fa-chevron-circle-left pictofixedwidth valignmiddle marginrightonlyshort nsinfo-toggle-menu" title="' . dol_escape_htmltag($titleminimize) . '" data-title-minimize="' . dol_escape_htmltag($titleminimize) . '" data-title-maximize="' . dol_escape_htmltag($titlemaximize) . '"></span>',
		'mainmenu' => $mainmenu,
		'leftmenu' => $mainmenu . '_nsinfominimizemenu',
		'url' => '',
		'langs' => 'nsinfo@nsinfo',
		'position' => (int) $position,
		'enabled' => $enabled,
		'perms' => '1',
		'target' => '',
		'user' => 2,
	);
}
