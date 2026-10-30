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
 * À ajouter aux menus de gauche d'un module, dans le constructeur du descripteur :
 *     dol_include_once('/nsinfo/lib/nsinfo.lib.php');
 *     if (function_exists('nsinfoMenuMinimize')) $this->menu[$r++] = nsinfoMenuMinimize('monmodule', 0, 'isModEnabled("monmodule")');
 * Position 0 (conseillé) : nsinfo la calcule (nsinfoMenuMinimizePosition()), toujours après les autres entrées du
 * module et sans collision avec l'entrée « Réduire le menu » d'un autre module (Dolibarr refuse deux entrées de même
 * position et de même url : « Menu entry (all,2012,) already exists »).
 *
 * @param	string	$mainmenu	Code du menu principal du module (ex : 'gmao')
 * @param	int		$position	Position de l'entrée, 0 = calculée par nsinfo (conseillé)
 * @param	string	$enabled	Condition d'affichage (dol_eval)
 * @return	array<string,mixed>	Entrée de menu pour $this->menu
 */
function nsinfoMenuMinimize($mainmenu, $position = 0, $enabled = '1')
{
	global $langs;

	if ((int) $position <= 0) {
		$position = nsinfoMenuMinimizePosition($mainmenu);
	}

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

/**
 * Position de l'entrée « Réduire le menu » d'un module : après les entrées du module (9000 et plus), stable d'une
 * activation à l'autre (calculée à partir du code du menu) et libre : une position déjà prise par une entrée sans url
 * d'un autre module (« Réduire le menu » de Saturne ou d'un autre module NS INFO) est sautée.
 *
 * @param	string	$mainmenu	Code du menu principal du module
 * @return	int					Position
 */
function nsinfoMenuMinimizePosition($mainmenu)
{
	global $db, $conf;

	$position = 9000 + (abs(crc32((string) $mainmenu)) % 900);
	if (!is_object($db)) {
		return $position;
	}

	// Positions already used by an entry without url of another module (the own entry of the module is replaced at activation)
	$used = array();
	$sql = "SELECT m.position FROM ".$db->prefix()."menu as m";
	$sql .= " WHERE (m.url = '' OR m.url IS NULL)";
	$sql .= " AND COALESCE(m.leftmenu, '') <> '".$db->escape($mainmenu.'_nsinfominimizemenu')."'";
	$sql .= " AND m.entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$used[(int) $obj->position] = 1;
		}
		$db->free($resql);
	}
	while (isset($used[$position])) {
		$position++;
	}

	return $position;
}

/**
 * Groupe de menus déroulant (js/modules/menu.js) : marqueur de l'entrée parente, à ajouter au « prefix » de l'entrée
 * de menu de gauche qui contiendra le groupe. Une flèche y est ajoutée pour déplier / replier ses propres sous-menus
 * et les entrées du groupe (seule, sans entrée du groupe, elle rend simplement ses sous-menus déroulants).
 *     'prefix' => img_picto('', 'monicone.png@monmodule', 'class="pictofixedwidth valignmiddle marginrightonlyshort"').nsinfoMenuGroupParent('monmodule_groupe'),
 *
 * @param	string	$group		Identifiant du groupe (lettres, chiffres, tiret, souligné), unique dans le menu
 * @param	int		$outside	1 = groupe affiché juste sous le bloc de l'entrée parente, hors de ce bloc (par exemple
 *								pour garder un cadre autour de l'entrée seule) ; 0 = dans le bloc de l'entrée parente
 * @return	string				HTML à ajouter au prefix
 */
function nsinfoMenuGroupParent($group, $outside = 0)
{
	// Marqueur court : le prefix est enregistré dans llx_menu.prefix (255 caractères), avec l'image de l'entrée
	return '<i class="nsinfo-mgp" data-mg="' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $group) . '"' . ($outside ? ' data-mgout="1"' : '') . '></i>';
}

/**
 * Groupe de menus déroulant (js/modules/menu.js) : marqueur d'une entrée du groupe, à ajouter au « prefix » de chaque
 * entrée de menu de gauche (niveau 0) à ranger, avec ses sous-menus, sous l'entrée parente du même groupe.
 *     'prefix' => img_picto('', 'monicone.png@monmodule', 'class="pictofixedwidth valignmiddle marginrightonlyshort"').nsinfoMenuGroupChild('monmodule_groupe'),
 *
 * @param	string	$group	Identifiant du groupe, le même que pour nsinfoMenuGroupParent()
 * @return	string			HTML à ajouter au prefix
 */
function nsinfoMenuGroupChild($group)
{
	return '<i class="nsinfo-mgc" data-mg="' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $group) . '"></i>';
}

/**
 * Calendrier de plage entre deux dates (js/modules/rangePicker.js) : sélection à la souris ou au doigt par glisser d'un
 * jour à l'autre (la plage donne les bornes début et fin) ou un clic sur le premier jour puis un clic sur le dernier,
 * deux mois côte à côte, feuille en bas d'écran sur mobile. Retourne la balise script (une seule fois par page) ;
 * ensuite, en JavaScript :
 *     nsinfoRangePicker(ancre, {start: 'AAAA-MM-JJ', end: 'AAAA-MM-JJ', workdays: [1,2,3,4,5], lang: 'fr-FR',
 *         labels: {today: '', clear: '', days: '', from: '', to: '', pick: ''}, onChange: function (start, end) {}});
 * Pastille cliquable conseillée pour afficher une date : <span class="nsinfo-period-chip">…</span>
 *
 * @return	string	HTML (balise script)
 */
function nsinfoRangePickerLoad()
{
	static $loaded = 0;

	if ($loaded) {
		return '';
	}
	$loaded = 1;
	$version = @file_get_contents(dol_buildpath('/nsinfo/VERSION', 0));

	return '<script src="' . dol_buildpath('/nsinfo/js/modules/rangePicker.js', 1) . '?v=' . urlencode(trim((string) $version)) . '"></script>' . "\n";
}

/**
 * Charge l'horloge de choix d'une heure (js/modules/timePicker.js, à la manière d'Android : l'heure sur le cadran, puis
 * les minutes), une seule fois par page. À appeler avant le JavaScript qui l'utilise :
 *     print nsinfoTimePickerLoad();
 * ensuite, en JavaScript :
 *     nsinfoTimePicker(ancre, {value: 'HH:MM', lang: 'fr-FR', step: 1,
 *         labels: {ok: '', cancel: '', now: '', clear: ''}, onChange: function (hhmm) {}});
 * Pastille cliquable conseillée pour afficher l'heure : <span class="nsinfo-period-chip">…</span>
 *
 * @return	string	HTML (balise script)
 */
function nsinfoTimePickerLoad()
{
	static $loaded = 0;

	if ($loaded) {
		return '';
	}
	$loaded = 1;
	$version = @file_get_contents(dol_buildpath('/nsinfo/VERSION', 0));

	return '<script src="' . dol_buildpath('/nsinfo/js/modules/timePicker.js', 1) . '?v=' . urlencode(trim((string) $version)) . '"></script>' . "\n";
}

/**
 * Zone de dépôt de fichiers (glisser-déposer ou clic), script js/modules/dropZone.js : les fichiers déposés ou choisis
 * sont envoyés aussitôt par un formulaire POST (multipart) vers $url, avec le jeton de sécurité, l'action $action et
 * les champs cachés $hiddens. Côté page, les fichiers arrivent dans $_FILES[$htmlname] (plusieurs fichiers), par
 * exemple pour dol_add_file_process($upload_dir, 0, 1, $htmlname).
 *     print nsinfoDropZone('monfichier', $_SERVER['PHP_SELF'], 'addmonfichier', array('id' => $object->id));
 *
 * @param	string					$htmlname	Nom du champ fichier (lettres, chiffres, souligné)
 * @param	string					$url		Adresse du formulaire
 * @param	string					$action		Valeur du champ caché « action »
 * @param	array<string,string>	$hiddens	Autres champs cachés (nom => valeur)
 * @param	string					$label		Texte de la zone (par défaut : « Déposez vos fichiers ici ou cliquez pour les choisir »)
 * @param	string					$accept		Types de fichiers proposés par le sélecteur (attribut accept, ex : '.pdf,image/*')
 * @param	int						$disabled	1 = zone affichée inactive (pas de droit d'ajout)
 * @return	string								HTML de la zone (et chargement du script, une seule fois par page)
 */
function nsinfoDropZone($htmlname, $url, $action, $hiddens = array(), $label = '', $accept = '', $disabled = 0)
{
	global $langs;
	static $scriptloaded = 0;

	$langs->load('nsinfo@nsinfo');
	$htmlname = preg_replace('/[^a-zA-Z0-9_]/', '', $htmlname);
	if ($label === '') {
		$label = $langs->trans('NSINFO_DropZoneLabel');
	}

	$out = '';
	if (!$scriptloaded) {
		$version = @file_get_contents(dol_buildpath('/nsinfo/VERSION', 0));
		$out .= '<script src="' . dol_buildpath('/nsinfo/js/modules/dropZone.js', 1) . '?v=' . urlencode(trim((string) $version)) . '"></script>' . "\n";
		$scriptloaded = 1;
	}

	$out .= '<form class="nsinfo-dropzone-form" method="POST" enctype="multipart/form-data" action="' . dol_escape_htmltag($url) . '">';
	$out .= '<input type="hidden" name="token" value="' . newToken() . '">';
	$out .= '<input type="hidden" name="action" value="' . dol_escape_htmltag($action) . '">';
	foreach ($hiddens as $key => $value) {
		$out .= '<input type="hidden" name="' . dol_escape_htmltag($key) . '" value="' . dol_escape_htmltag($value) . '">';
	}
	$out .= '<input type="file" class="nsinfo-dropzone-input hideobject" name="' . $htmlname . '[]" multiple' . ($accept ? ' accept="' . dol_escape_htmltag($accept) . '"' : '') . ($disabled ? ' disabled' : '') . '>';
	$out .= '<div class="nsinfo-dropzone' . ($disabled ? ' nsinfo-dropzone-disabled' : '') . '" role="button" tabindex="0" data-sending="' . dol_escape_htmltag($langs->trans('NSINFO_DropZoneSending')) . '">';
	$out .= '<span class="fas fa-cloud-upload-alt nsinfo-dropzone-icon"></span>';
	$out .= '<span class="nsinfo-dropzone-label">' . dol_escape_htmltag($label) . '</span>';
	$out .= '</div>';
	$out .= '</form>';

	return $out;
}
