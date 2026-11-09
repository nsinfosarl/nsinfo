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
 * \file    nsinfo/admin/config_module.php
 * \ingroup nsinfo
 * \brief   Backup of the settings of a NS INFO module in a JSON file, to transfer them to another Dolibarr : page called with ?namemodule=<module>, like changelog_module.php
 */

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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/nsinfo/lib/nsinfo.lib.php');

/**
 * @var Societe $mysoc
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');
$namemodule = preg_replace('/[^a-z0-9_]/', '', strtolower(GETPOST('namemodule', 'alpha')));
if ($namemodule === '' || !is_dir(dol_buildpath('/'.$namemodule, 0))) {
	$namemodule = 'nsinfo';
}
// Header of the module that shows the tab (same principle as changelog_module.php)
if ($namemodule == 'factory') {
	dol_include_once('/'.$namemodule.'/core/lib/'.$namemodule.'.lib.php');
	$namehead = 'factory_admin_prepare_head';
} else {
	dol_include_once('/'.$namemodule.'/lib/'.$namemodule.'.lib.php');
	$namehead = $namemodule.'AdminPrepareHead';
}
$langs->loadLangs(array("admin", "nsinfo@nsinfo", $namemodule.'@'.$namemodule));

if (!$user->admin) {
	accessforbidden();
}

$prefix = strtoupper($namemodule).'_';
$token = newToken();
$subtab = GETPOST('subtab', 'aZ09') == 'import' ? 'import' : 'export';

/**
 * Constants of the module (entity in use) : name => array(value, type)
 *
 * @param	DoliDB	$db			Database
 * @param	string	$prefix		Prefix of the constants (MODULE_)
 * @return	array<string,array{0:string,1:string}>
 */
function nsinfoCfgConstants($db, $prefix)
{
	global $conf;
	$out = array();
	$sql = "SELECT name, value, type FROM ".MAIN_DB_PREFIX."const WHERE entity = ".((int) $conf->entity)." AND name LIKE '".$db->escape(str_replace(array('_', '%'), array('\\_', '\\%'), $prefix))."%' ORDER BY name";
	$resql = $db->query($sql);
	while ($resql && ($obj = $db->fetch_object($resql))) {
		$out[$obj->name] = array((string) $obj->value, (string) $obj->type);
	}
	return $out;
}

/**
 * Tells if a constant looks like a secret (password, key, token) : not exported unless asked
 *
 * @param	string	$name	Name of the constant
 * @return	bool
 */
function nsinfoCfgIsSecret($name)
{
	return (bool) preg_match('/(PASS|PWD|SECRET|TOKEN|APIKEY|API_KEY|_KEY)/i', $name);
}

$all = nsinfoCfgConstants($db, $prefix);

/*
 * Actions
 */

$tokenok = (GETPOST('token', 'alpha') == currentToken());

// Download of the JSON file of the selected constants
if ($action == 'download' && $tokenok) {
	$selected = GETPOST('sel', 'array:aZ09');
	$withsecrets = GETPOSTINT('withsecrets');
	$constants = array();
	foreach ($all as $name => $info) {
		if (!in_array($name, $selected, true) || (nsinfoCfgIsSecret($name) && !$withsecrets)) {
			continue;
		}
		$constants[$name] = array('value' => $info[0], 'type' => $info[1]);
	}
	$data = array(
		'format' => 'nsinfo-module-config',
		'module' => $namemodule,
		'module_version' => trim((string) @file_get_contents(dol_buildpath('/'.$namemodule.'/VERSION', 0))),
		'company' => (string) $mysoc->name,
		'dolibarr' => DOL_VERSION,
		'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S'),
		'secrets' => $withsecrets ? 1 : 0,
		'constants' => $constants,
	);
	header('Content-Type: application/json; charset=utf-8');
	// The name of the file carries the name of the module, the name of the company and the date of creation
	$company = trim(preg_replace('/[^A-Za-z0-9]+/', '_', dol_string_unaccent((string) $mysoc->name)), '_');
	header('Content-Disposition: attachment; filename="'.$namemodule.'_parametres'.($company !== '' ? '_'.$company : '').'_'.dol_print_date(dol_now(), '%Y-%m-%d_%H%M').'.json"');
	print json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
	exit;
}

// Import of a JSON file (dropped file or pasted text)
if ($action == 'import' && $tokenok) {
	$text = '';
	$dropped = (!empty($_FILES['nsinfo_cfg']['tmp_name'][0]) && is_uploaded_file($_FILES['nsinfo_cfg']['tmp_name'][0])) ? $_FILES['nsinfo_cfg']['tmp_name'][0] : '';
	if ($dropped !== '') {
		$text = (string) file_get_contents($dropped);
	} else {
		$text = (string) GETPOST('jsontext', 'restricthtml');
	}
	$data = json_decode($text, true);
	if (!is_array($data) || ($data['format'] ?? '') !== 'nsinfo-module-config' || !isset($data['constants']) || !is_array($data['constants'])) {
		setEventMessages($langs->trans('NsinfoCfgInvalid'), null, 'errors');
	} elseif (($data['module'] ?? '') !== $namemodule) {
		setEventMessages($langs->trans('NsinfoCfgOtherModule', dol_escape_htmltag((string) ($data['module'] ?? '?')), $namemodule), null, 'errors');
	} else {
		$nb = 0;
		$skipped = 0;
		$db->begin();
		foreach ($data['constants'] as $name => $info) {
			// Only the constants of the module, never the ones of the core or of another module
			if (!is_string($name) || strpos($name, $prefix) !== 0 || !preg_match('/^[A-Z0-9_]+$/', $name) || !is_array($info) || !isset($info['value']) || !is_scalar($info['value'])) {
				$skipped++;
				continue;
			}
			$type = in_array(($info['type'] ?? ''), array('chaine', 'yesno', 'int'), true) ? $info['type'] : 'chaine';
			if (dolibarr_set_const($db, $name, (string) $info['value'], $type, 0, '', $conf->entity) > 0) {
				$nb++;
			} else {
				$skipped++;
			}
		}
		// A setting of the module that is not in the file goes back to zero (constant deleted : the module uses its default).
		// Kept : the markers of first activation (*_INIT) and the sensitive settings when the file was made without them
		$reset = 0;
		foreach ($all as $name => $info) {
			if (array_key_exists($name, $data['constants']) || substr($name, -5) === '_INIT' || (nsinfoCfgIsSecret($name) && empty($data['secrets']))) {
				continue;
			}
			if (dolibarr_del_const($db, $name, $conf->entity) > 0) {
				$reset++;
			}
		}
		$db->commit();
		setEventMessages($langs->trans('NsinfoCfgImported', $nb), null, 'mesgs');
		if ($reset > 0) {
			setEventMessages($langs->trans('NsinfoCfgReset', $reset), null, 'mesgs');
		}
		if ($skipped > 0) {
			setEventMessages($langs->trans('NsinfoCfgSkipped', $skipped), null, 'warnings');
		}
		$all = nsinfoCfgConstants($db, $prefix);
	}
}

/*
 * View
 */

$form = new Form($db);
$module = $langs->trans("Module".ucfirst($namemodule)."Name");
$page_name = $module.' - '.$langs->trans('NsinfoCfgTab');
llxHeader('', $page_name, '', '', 0, 0, '', '', '', 'mod-'.$namemodule.' page-admin');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($page_name, $linkback, "object_{$namemodule}@{$namemodule}");

$head = $namehead();
$picto = ($namemodule == 'nsinfo') ? "object_nsinfo.png@nsinfo" : "{$namemodule}@{$namemodule}";
print dol_get_fiche_head($head, 'configmodule', $langs->trans('NsinfoCfgTab'), -1, $picto);

// Horizontal sub tabs : export / import
$self = $_SERVER['PHP_SELF'].'?namemodule='.urlencode($namemodule);
$subhead = array(
	array($self.'&subtab=export', '<span class="fas fa-file-export pictofixedwidth" style="color: #E67E22"></span>'.$langs->trans('NsinfoCfgExport'), 'export'),
	array($self.'&subtab=import', '<span class="fas fa-file-import pictofixedwidth" style="color: #3879B2"></span>'.$langs->trans('NsinfoCfgImport'), 'import'),
);
print dol_get_fiche_head($subhead, $subtab, '', -1, '');
print '<style nonce="'.getNonce().'">.nsinfo-cfgbtn { display: inline-block; min-width: 170px; text-align: center; margin: 0 !important; box-sizing: border-box; }</style>';
print '<span class="opacitymedium">'.$langs->trans('NsinfoCfgIntro').'</span><br><br>';

if ($subtab == 'export') {
	print '<form method="POST" action="'.dol_escape_htmltag($self).'&subtab=export">';
	print '<input type="hidden" name="token" value="'.$token.'"><input type="hidden" name="action" value="download">';
	$nbsecret = 0;
	foreach ($all as $n => $i) {
		$nbsecret += nsinfoCfgIsSecret($n) ? 1 : 0;
	}
	print '<p>'.$langs->trans('NsinfoCfgCount', count($all), $nbsecret).'</p>';
	print '<label><input type="checkbox" id="nsinfocfgdetail"> '.$langs->trans('NsinfoCfgShowDetail').'</label><br><br>';
	print '<div id="nsinfocfglist" class="div-table-responsive-no-min" style="display: none"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield"><input type="checkbox" id="nsinfocfgall" checked> '.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>';
	if (empty($all)) {
		print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans('NsinfoCfgNone').'</td></tr>';
	}
	foreach ($all as $name => $info) {
		$secret = nsinfoCfgIsSecret($name);
		$val = $secret ? str_repeat('•', 8) : dol_escape_htmltag(dol_trunc($info[0], 120));
		print '<tr class="oddeven"><td><input type="checkbox" class="nsinfocfgsel" name="sel[]" value="'.dol_escape_htmltag($name).'"'.($secret ? '' : ' checked').'> <code>'.dol_escape_htmltag($name).'</code>';
		if ($secret) {
			print ' <span class="fas fa-lock opacitymedium" title="'.dol_escape_htmltag($langs->trans('NsinfoCfgSecret')).'"></span>';
		}
		print '</td><td class="small">'.$val.'</td></tr>';
	}
	print '</table></div><br>';
	print '<label><input type="checkbox" name="withsecrets" value="1"> '.$langs->trans('NsinfoCfgWithSecrets').'</label><br><br>';
	print '<div class="center"><button type="submit" class="butAction nsinfo-cfgbtn"'.(empty($all) ? ' disabled' : '').'><span class="fas fa-download paddingright"></span>'.$langs->trans('Download').'</button></div>';
	print '</form>';
	print '<script nonce="'.getNonce().'">document.getElementById("nsinfocfgdetail").addEventListener("change",function(){document.getElementById("nsinfocfglist").style.display=this.checked?"":"none";});document.getElementById("nsinfocfgall").addEventListener("change",function(){var c=this.checked;document.querySelectorAll(".nsinfocfgsel").forEach(function(e){e.checked=c;});});</script>';
}

if ($subtab == 'import') {
	print nsinfoDropZone('nsinfo_cfg', $self, 'import', array('subtab' => 'import'), $langs->trans('NsinfoCfgDrop'), '.json,application/json');
	print '<br>';
	print '<form method="POST" action="'.dol_escape_htmltag($self).'&subtab=import"><input type="hidden" name="token" value="'.$token.'">';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td><td class="center width200">'.$langs->trans('Action').'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans('NsinfoCfgOrPaste').'</td><td><textarea name="jsontext" class="flat" rows="6" style="font-family: monospace; width: 100%; box-sizing: border-box"></textarea></td>';
	print '<td class="center"><button type="submit" name="action" value="import" class="butAction smallpaddingimp nsinfo-cfgbtn">'.$langs->trans('NsinfoCfgImport').'</button></td></tr>';
	print '</table></div></form>';
	print '<br><div class="info">'.$langs->trans('NsinfoCfgImportHelp', $prefix).'</div>';
}

print dol_get_fiche_end();
print dol_get_fiche_end();
llxFooter();
$db->close();
