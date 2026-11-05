<?php
/* Copyright (C) 2026 Nicolas Silobre <contact@ns-info.fr>
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
 *	\file       nsinfo/ajax/translate.php
 *	\ingroup    nsinfo
 *	\brief      Automatic translation of a text for all the modules (JSON answer, nothing is saved).
 *	            POST : token, module (module that calls : its settings are used, common or specific), text, to (index 1..5 of the target language), html (0/1), from (index of the source language,
 *	            by default the language of the user). Indexes : 1 en_US, 2 fr_FR, 3 de_DE, 4 it_IT, 5 es_ES.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

dol_include_once('/nsinfo/lib/autotrad.lib.php');
nsinfoTradContext(GETPOST('module', 'alpha'));

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('nsinfo@nsinfo'));

$answer = array();
if (!empty($user->socid) || !$user->id) {
	$answer = array('error' => $langs->transnoentities('NotEnoughPermissions'));
} elseif (nsinfoTradActiveProvider() === '') {
	$answer = array('error' => $langs->transnoentities('NsinfoTradNotConfigured'));
} else {
	$from = GETPOSTINT('from') ? GETPOSTINT('from') : nsinfoTradLangIndex($user->lang ? $user->lang : $langs->defaultlang);
	$error = '';
	$text = nsinfoTradTranslate((string) GETPOST('text', 'restricthtml'), $from, GETPOSTINT('to'), (bool) GETPOSTINT('html'), $error);
	if ($text === null) {
		$trans = $langs->transnoentities($error);
		$answer = array('error' => ($trans !== $error ? $trans : $error));
	} else {
		$answer = array('text' => $text);
	}
}
top_httphead('application/json');
print json_encode($answer);
exit;
