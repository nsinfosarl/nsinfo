<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2024 Nicolas SILOBRE <contact@ns-info.fr>
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
 * \file    dpersoplus/admin/about.php
 * \ingroup dpersoplus
 * \brief   Page À propos du module dpersoplus.
 */

// Charge l’environnement Dolibarr
$res = 0;
// Essaie main.inc.php dans la racine web connue, définie dans CONTEXT_DOCUMENT_ROOT (pas toujours définie)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Essaie main.inc.php dans la racine web détectée à partir de SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Essaie main.inc.php avec un chemin relatif
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Bibliothèques
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once('/nsinfo/lib/nsinfo.lib.php');
dol_include_once('/nsinfo/lib/setup.lib.php');



// Paramètres
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$namemodule = preg_replace('/[^a-z0-9_]/', '', strtolower(GETPOST('namemodule', 'alpha')));	// nom de module seul (inclusion de fichiers)

$modM = ucfirst($namemodule);

// Contrôle d’accès
if ($namemodule == 'gmao') {
	$permissiontoread=$user->rights->gmao->gmao->admin;
	nsinfo_check_access($permissiontoread);
}
else if (!nsinfoCanSetup($namemodule)) accessforbidden();	// administrateur ou droit « Réglages du module »
if ($namemodule == 'factory') dol_include_once('/'.$namemodule.'/core/lib/'.$namemodule.'.lib.php');
else dol_include_once('/'.$namemodule.'/lib/'.$namemodule.'.lib.php');
// Traductions
$langs->loadLangs(array("errors", "admin", "dpersoplus@dpersoplus", "nsinfo@nsinfo", "{$namemodule}@{$namemodule}"));

/*
 * Actions
 */

// Aucune


/*
 * Affichage
 */

$form = new Form($db);
$module=$langs->trans("Module".ucfirst("{$namemodule}")."Name");

$page_name = $module . " - ChangeLog";
llxHeader('', $langs->trans($page_name));

// En-tête secondaire
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, "object_{$namemodule}@{$namemodule}");

// En-tête de configuration

if ($namemodule == 'factory') $namehead = 'factory_admin_prepare_head';
else $namehead = $namemodule."AdminPrepareHead";
$head = $namehead();

$picto = "{$namemodule}@{$namemodule}";
if ($namemodule == 'nsinfo') $picto = "object_nsinfo.png@{$namemodule}";

print dol_get_fiche_head($head, 'changelog', $langs->trans("ChangeLog"), -1, $picto);

dol_include_once('/'.$namemodule.'/core/modules/mod'.ucfirst($namemodule).'.class.php');

$nameMod = "mod".$modM;
$tmpmodule = new $nameMod($db);

$moduleUrlPath='';

print '<a href="https://www.ns-info.fr" border=0 target="_blank"><img src="../img/nsinfo.png" width="200" align="right"></a>';
$modulePath = dol_buildpath('/'.strtolower($namemodule), 0);
$pathoffile = $modulePath . '/ChangeLog.md';
$moduleUrlPath = dol_buildpath('/'.strtolower($namemodule), 1);

$content  = '';
$content = file_get_contents($pathoffile);
@include_once DOL_DOCUMENT_ROOT . '/core/lib/parsemd.lib.php';
$content = dolMd2Html($content, 'parsedown', array('doc/' => $moduleUrlPath . '/doc/'));
print $content;


// Fin de page
print dol_get_fiche_end();
//dol_fiche_end();
llxFooter();
$db->close();
