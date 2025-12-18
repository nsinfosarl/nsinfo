<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022-2024 Nicolas SILOBRE <contact@ns-info.fr>
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
 * \file    nsinfo/admin/about.php
 * \ingroup nsinfo
 * \brief   About page of module Nsinfo.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/nsinfo/lib/nsinfo.lib.php');
dol_include_once('/nsinfo/lib/setup.lib.php');



// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$namemodule = GETPOST('namemodule', 'alpha');

$modM = ucfirst($namemodule);
$nameMod = 'mod'.$modM;
if ($namemodule == 'factory') {
	dol_include_once('/'.$namemodule.'/core/lib/'.$namemodule.'.lib.php');
}
else {
	dol_include_once('/'.$namemodule.'/lib/'.$namemodule.'.lib.php');
}
dol_include_once('/'.$namemodule.'/core/modules/'.$nameMod.'.class.php');


// Access control
if ($namemodule == 'gmao') {
	$permissiontoread=$user->rights->gmao->gmao->admin;
	nsinfo_check_access($permissiontoread);
}
else if (!$user->admin) accessforbidden();
// Translations
$langs->loadLangs(array("errors", "admin", "nsinfo@nsinfo", "{$namemodule}@{$namemodule}"));
$version_nsinfo = getVersionModule('nsinfo');
$modClass = new $nameMod($db);
$constantLastVersion = !empty($modClass->getVersion()) ? $modClass->getVersion() : 'NC';
$supportvalue = "/*****"."<br>";
$supportvalue.= " * Module NSINFO ".$version_nsinfo."<br>";
$supportvalue.= " * Module : ".$modClass->name." / ".$langs->trans('Module'.$modClass->name.'Name')."<br>";
$supportvalue.= " * Module version : ".$constantLastVersion."<br>";
$supportvalue.= " * Dolibarr version : ".DOL_VERSION."<br>";
$supportvalue.= " * Dolibarr version installation initiale : ".$conf->global->MAIN_VERSION_LAST_INSTALL."<br>";
$supportvalue.= " * Option colonne sélection à gauche : ".(isset($conf->global->MAIN_CHECKBOX_LEFT_COLUMN) && (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) ? $conf->global->MAIN_CHECKBOX_LEFT_COLUMN : 0)."<br>";
$supportvalue.= " * Version PHP : ".PHP_VERSION."<br>";
$supportvalue.= " *****/"."<br>";
$supportvalue.= "Description de votre problème :"."<br>";

// Initialize technical objects
$className = 'mod' . ucfirst($namemodule);
$modModule = new $className($db);

/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$module=$langs->trans("Module".ucfirst("{$namemodule}")."Name");

$page_name = $module . " - ".$langs->trans("About");

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, "object_{$namemodule}@{$namemodule}");

// Configuration header
if ($namemodule == 'factory') $namehead = 'factory_admin_prepare_head';
else $namehead = $namemodule."AdminPrepareHead";
$head = $namehead();

print dol_get_fiche_head($head, 'about', $langs->trans("About"), -1, "{$namemodule}@{$namemodule}");

dol_include_once('/'.$namemodule.'/core/modules/mod'.ucfirst($namemodule).'.class.php');

$nameMod = "mod".$modM;
$tmpmodule = new $nameMod($db);
print '<form id="ticket" method="POST" target="_blank" action="https://gestdoli.ns-info90.fr/public/ticket/create_ticket.php">';
print '<input name=message type="hidden" value="'.$supportvalue.'" />';
print '<input name=type_code type="hidden" value="2" />';
print '<input name=severity_code type="hidden" value="NORMAL" />';
print '<input name=email type="hidden" value="'.$user->email.'" />';
print '<B>';
print '<a href="https://www.ns-info.fr" border=0 target="_blank"><img src="../img/nsinfo.png" width="200" align="right"></a>';
print '</Br>';
print '</Br>';
print '</Br>';
print '<div align="right">Module NSINFO '.$version_nsinfo.'</div>';
print '</B>';
print '<table width="100%"><tr>' . "\n";
print '<td width="310px"><a href="https://www.ns-info.fr" border=0 target="_blank"><img src="../img/nsinfo.png" width="300" /><br>';
print '<div style="text-align: right"><img src="../img/doli-pp.png" width="150" /></div>';
print '</a></td>' . "\n";
print '<td>&nbsp;&nbsp;</td>';
print '<td align="left" valign="top"><p>' . $langs->trans("NSINFOAboutDesc1") .
	$langs->trans("NSINFOAboutDesc2").
	$langs->trans("NSINFOAboutDesc3").
	$langs->trans("NSINFOAboutDesc4").' '.
	'<button type="submit">'.$langs->trans("NSINFOAboutDesc45").'</button>';
if (strlen($langs->transnoentities("NSINFOAboutDesc5")) > 16 && $namemodule == 'gmao') print $langs->transnoentities("NSINFOAboutDesc5");
print '</p></td>' . "\n";

print '</tr><tr>';
print '<td width="310px">&nbsp;</td>' . "\n";
print '<td>&nbsp;&nbsp;</td>';
print '<td align="left" valign="top"><p>'. $langs->trans("NSINFOAboutDescDolistore").' <a href="https://www.dolistore.com/index.php?controller=search&orderby=position&orderway=desc&tag=&website=marketplace&search_query=NSINFO" border=0 target="_blank"><img src="../img/dolistore.png" width="100" /></a>';

print '</tr>';

print '</table>' . "\n";

print $modModule->getDescLong();

// Page end
dol_fiche_end();
llxFooter();
$db->close();
