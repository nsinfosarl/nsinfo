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
 * \file    nsinfo/admin/trad_module.php
 * \ingroup toolsdolistore
 * \brief   Réglage de la traduction automatique, partagé par les modules NS INFO : page appelée avec ?namemodule=<module> (API de traduction choisie dans une liste, ses réglages et ses conditions d'utilisation)
 */

// Chargement de l'environnement Dolibarr
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
dol_include_once('/nsinfo/lib/nsinfo.lib.php');
dol_include_once('/nsinfo/lib/autotrad.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$namemodule = preg_replace('/[^a-z0-9_]/', '', strtolower(GETPOST('namemodule', 'alpha')));
if ($namemodule === '' || !is_dir(dol_buildpath('/'.$namemodule, 0))) {
	$namemodule = 'nsinfo';
}
// En-tête du module qui affiche l'onglet (même principe que changelog_module.php)
if ($namemodule == 'factory') {
	dol_include_once('/'.$namemodule.'/core/lib/'.$namemodule.'.lib.php');
	$namehead = 'factory_admin_prepare_head';
} else {
	dol_include_once('/'.$namemodule.'/lib/'.$namemodule.'.lib.php');
	$namehead = $namemodule.'AdminPrepareHead';
}
$langs->loadLangs(array("admin", "nsinfo@nsinfo", $namemodule.'@'.$namemodule));

// Réglages communs à tous les modules, ou propres au module (option affichée ci-dessous)
nsinfoTradContext($namemodule);
$prefix = nsinfoTradPrefix();
$forcedscope = nsinfoTradForcedScope($namemodule);	// Périmètre imposé par le module (<module>TradScope()), '' = choix libre
$canscope = ($namemodule != 'nsinfo');

// Administrateurs seulement : la page lit et modifie les réglages communs de traduction (clés d’API des fournisseurs, partagées
// par tous les modules), le droit « Réglages du module » d’un module ne suffit donc pas
if (!$user->admin) {
	accessforbidden();
}
$providers = nsinfoTradProviders();

/*
 * Actions
 */

// Enregistrement d'un réglage (appelé à chaque modification) : le fournisseur, ou un réglage d'un fournisseur
if ($action == 'setconst') {
	$name = GETPOST('name', 'aZ09');
	$value = trim((string) GETPOST('value', 'restricthtml'));
	$allowed = array($prefix.'PROVIDER', $prefix.'POSITION', $prefix.'COLOR', nsinfoTradFieldsConst());
	if ($canscope) {
		$allowed[] = 'NSINFO_TRAD_SCOPE_'.strtoupper($namemodule);
	}
	foreach ($providers as $code => $p) {
		foreach ($p['settings'] as $s) {
			$allowed[] = $prefix.strtoupper($code).'_'.$s['name'];
		}
	}
	$ok = 0;
	if (in_array($name, $allowed)) {
		if ($name == $prefix.'PROVIDER' && $value !== '' && !isset($providers[$value])) {
			$value = '';
		}
		if ($name == nsinfoTradFieldsConst()) {
			// Uniquement les champs listés par le module
			$known = function_exists($namemodule.'TradFields') ? array_keys(call_user_func($namemodule.'TradFields')) : array();
			$value = implode(',', array_intersect(array_map('trim', explode(',', $value)), $known));
		}
		if ($name == $prefix.'COLOR' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
			$value = '';
		}
		$ok = dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $conf->entity) > 0 ? 1 : 0;
	}
	top_httphead('application/json');
	print json_encode(array('saved' => $ok));
	exit;
}

// Test du fournisseur : traduction d'une courte phrase du français vers l'anglais
if ($action == 'testtrad') {
	$error = '';
	$out = nsinfoTradTranslate('Bonjour, ceci est un test de traduction.', 2, 1, false, $error);
	top_httphead('application/json');
	if ($out === null) {
		$trans = $langs->transnoentities($error);
		print json_encode(array('error' => ($trans !== $error ? $trans : $error)));
	} else {
		print json_encode(array('text' => $out));
	}
	exit;
}

/*
 * Affichage
 */

$modulelabel = $langs->trans('Module'.ucfirst($namemodule).'Name');
$page_name = $modulelabel.' - '.$langs->transnoentitiesnoconv('NsinfoTradTab');
llxHeader('', $page_name);

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($page_name, $linkback, 'object_'.$namemodule.'@'.$namemodule);

$head = function_exists($namehead) ? $namehead() : array();
print dol_get_fiche_head($head, 'trad', $langs->trans("NsinfoTradTab"), -1, $namemodule.'@'.$namemodule);

$current = getDolGlobalString($prefix.'PROVIDER');
print '<div class="opacitymedium marginbottomonly">'.$langs->trans('NsinfoTradIntro').'</div>';

// Sous-onglets : API (fournisseur, périmètre, position, couleur) et champs qui ont le picto (liste fournie par le module)
$hasfields = function_exists($namemodule.'TradFields');
$subtab = ($hasfields && GETPOST('subtab', 'aZ09') == 'fields') ? 'fields' : 'api';
if ($hasfields) {
	$subhead = array();
	$subhead[0] = array(dol_buildpath('/nsinfo/admin/trad_module.php', 1).'?namemodule='.$namemodule.'&subtab=api', '<i class="fas fa-plug paddingright pictofixedwidth valignmiddle" style="color: #2E7D32"></i>'.$langs->trans('NsinfoTradSubApi'), 'api');
	$subhead[1] = array(dol_buildpath('/nsinfo/admin/trad_module.php', 1).'?namemodule='.$namemodule.'&subtab=fields', '<i class="fas fa-list-check paddingright pictofixedwidth valignmiddle" style="color: #3879B2"></i>'.$langs->trans('NsinfoTradFields'), 'fields');
	dol_include_once('/nsinfo/lib/setup.lib.php');
	nsinfoVerticalTabsStart($subhead, $subtab);
}

print '<style>
.tdl-prov { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 14px; margin: 12px 0; }
.tdl-prov > div { border: 1px solid rgba(128,128,128,.3); border-top: 4px solid var(--c); border-radius: 8px; padding: 12px 16px; }
.tdl-prov > div.on { box-shadow: 0 0 0 2px var(--c); }
.tdl-prov h3 { margin: 0 0 6px 0; font-size: 1em; }
.tdl-prov h3 .fab, .tdl-prov h3 .fas { color: var(--c); margin-right: 6px; }
.tdl-prov ul { margin: 6px 0 6px 18px; padding: 0; }
#tdl-conf { border: 1px solid rgba(128,128,128,.3); border-radius: 8px; padding: 12px 16px; margin-top: 12px; }
.tdl-pos { display: flex; gap: 12px; flex-wrap: wrap; }
.tdl-pos-tile { border: 2px solid rgba(128,128,128,.3); border-radius: 8px; padding: 10px 12px; cursor: pointer; width: 190px; text-align: center; }
.tdl-pos-tile:hover { border-color: rgba(128,128,128,.7); }
.tdl-pos-tile.on { border-color: #5A2D82; box-shadow: 0 0 0 2px rgba(90,45,130,.2); }
.tdl-pos-prev { display: flex; align-items: center; justify-content: center; gap: 6px; height: 46px; }
.tdl-pos-field { position: relative; display: inline-block; width: 110px; height: 34px; border: 1px solid rgba(128,128,128,.6); border-radius: 4px; background: rgba(128,128,128,.08); }
.tdl-pos-top .tdl-pos-field i, .tdl-pos-bottom .tdl-pos-field i { position: absolute; right: 5px; font-size: 13px; }
.tdl-pos-top .tdl-pos-field i { top: 3px; }
.tdl-pos-bottom .tdl-pos-field i { bottom: 3px; }
.tdl-pos-label { margin-top: 6px; font-size: .9em; }
.tdl-fields { display: flex; flex-direction: column; gap: 4px; margin-bottom: 4px; }
.tdl-ok { color: #2E7D32; margin-left: 6px; display: none; }
</style>';

if ($subtab == 'fields') {
// Champs qui ont le picto : uniquement ceux cochés (le module liste ses champs traduisibles avec <module>TradFields())
$ticked = nsinfoTradFields();
print '<table class="border centpercent"><tr><td class="titlefield">'.$langs->trans('NsinfoTradFields').'</td><td><div class="tdl-fields">';
foreach (call_user_func($namemodule.'TradFields') as $code => $label) {
	print '<label><input type="checkbox" class="tdl-field" value="'.dol_escape_htmltag($code).'"'.(in_array($code, $ticked, true) ? ' checked' : '').'> '.dol_escape_htmltag($label).'</label>';
}
print '</div><span class="tdl-ok">&#10004;</span><br><span class="opacitymedium small">'.$langs->trans('NsinfoTradFieldsHelp').'</span></td></tr></table>';
} else {
// Liste des fournisseurs
print '<table class="border centpercent">';
if ($canscope && $forcedscope !== '') {
	print '<tr><td class="titlefield">'.$langs->trans('NsinfoTradScope').'</td><td>'.dol_escape_htmltag($forcedscope == 'module' ? $langs->transnoentitiesnoconv('NsinfoTradScopeModule', $modulelabel) : $langs->transnoentitiesnoconv('NsinfoTradScopeGlobal')).'<br><span class="opacitymedium small">'.$langs->trans('NsinfoTradScopeForced').'</span></td></tr>';
} elseif ($canscope) {
	// Réglages communs à tous les modules (Dolibarr) ou propres à ce module
	print '<tr><td class="titlefield">'.$langs->trans('NsinfoTradScope').'</td><td><select id="tdl-scope" class="flat minwidth300">';
	print '<option value="global"'.(nsinfoTradIsModuleScope() ? '' : ' selected').'>'.dol_escape_htmltag($langs->transnoentitiesnoconv('NsinfoTradScopeGlobal')).'</option>';
	print '<option value="module"'.(nsinfoTradIsModuleScope() ? ' selected' : '').'>'.dol_escape_htmltag($langs->transnoentitiesnoconv('NsinfoTradScopeModule', $modulelabel)).'</option>';
	print '</select><span class="tdl-ok">&#10004;</span><br><span class="opacitymedium small">'.$langs->trans('NsinfoTradScopeHelp').'</span></td></tr>';
}
print '<tr><td class="titlefield">'.$langs->trans('NsinfoTradProvider').'</td><td>';
print '<select id="tdl-provider" class="flat minwidth300"><option value="">'.$langs->trans('NsinfoTradNone').'</option>';
foreach ($providers as $code => $p) {
	print '<option value="'.$code.'"'.($code == $current ? ' selected' : '').'>'.dol_escape_htmltag($p['label']).'</option>';
}
print '</select><span class="tdl-ok" id="tdl-ok-provider">&#10004;</span></td></tr>';
// Position du picto de traduction à côté des champs (affiché seulement lorsqu'une API est active)
$position = nsinfoTradPosition();
if (function_exists($namemodule.'TradPositionForced')) {	// Un module peut imposer la position (la fonction <module>TradPositionForced() renvoie une note) : pas de choix, la note est affichée
print '<tr><td class="titlefield">'.$langs->trans('NsinfoTradPosition').'</td><td>'.dol_escape_htmltag(call_user_func($namemodule.'TradPositionForced')).'</td></tr>';
} else {
print '<tr><td class="titlefield">'.$langs->trans('NsinfoTradPosition').'</td><td><div class="tdl-pos">';
foreach (array('right' => 'NsinfoTradPosRight', 'top' => 'NsinfoTradPosTop', 'bottom' => 'NsinfoTradPosBottom') as $k => $key) {
	// Mini aperçu d'un champ avec le picto à la position choisie
	print '<div class="tdl-pos-tile'.($k == $position ? ' on' : '').'" data-value="'.$k.'" data-name="'.$prefix.'POSITION" tabindex="0" role="radio" aria-checked="'.($k == $position ? 'true' : 'false').'">';
	print '<div class="tdl-pos-prev tdl-pos-'.$k.'"><span class="tdl-pos-field">'.($k == 'right' ? '' : '<i class="fas fa-language tdl-sample" style="color: '.nsinfoTradColor().'"></i>').'</span>'.($k == 'right' ? '<i class="fas fa-language tdl-sample" style="color: '.nsinfoTradColor().'"></i>' : '').'</div>';
	print '<div class="tdl-pos-label">'.dol_escape_htmltag($langs->transnoentitiesnoconv($key)).'</div></div>';
}
print '</div><span class="tdl-ok">&#10004;</span><br><span class="opacitymedium small">'.$langs->trans('NsinfoTradPositionHelp').'</span></td></tr>';
}
// Couleur du picto (vert par défaut), enregistrée à chaque modification
print '<tr><td class="titlefield">'.$langs->trans('NsinfoTradColor').'</td><td><input type="color" class="tdl-set tdl-color" data-name="'.$prefix.'COLOR" value="'.nsinfoTradColor().'"><span class="tdl-ok">&#10004;</span> <a href="#" id="tdl-color-reset" class="opacitymedium small">'.$langs->trans('NsinfoTradColorReset').'</a></td></tr></table>';

// Réglages du fournisseur choisi
if (isset($providers[$current])) {
	$p = $providers[$current];
	print '<div id="tdl-conf"><h3 style="margin-top:0"><span class="'.(in_array($current, array('google', 'azure')) ? 'fab' : 'fas').' '.$p['picto'].'" style="color: '.$p['color'].'"></span> '.dol_escape_htmltag($p['label']).'</h3>';
	print '<table class="border centpercent">';
	foreach ($p['settings'] as $s) {
		$const = $prefix.strtoupper($current).'_'.$s['name'];
		$val = nsinfoTradSetting($current, $s['name']);
		print '<tr><td class="titlefield">'.$langs->trans($s['label']).(!empty($s['required']) ? ' <span class="star">*</span>' : '').'</td><td>';
		if ($s['type'] == 'select') {
			print '<select class="flat tdl-set minwidth300" data-name="'.$const.'">';
			foreach ($s['options'] as $k => $label) {
				print '<option value="'.$k.'"'.($k == $val ? ' selected' : '').'>'.dol_escape_htmltag($label).'</option>';
			}
			print '</select>';
		} else {
			print '<input type="'.($s['type'] == 'password' ? 'password' : 'text').'" class="flat tdl-set minwidth400" data-name="'.$const.'" value="'.dol_escape_htmltag($val).'" autocomplete="off">';
		}
		print '<span class="tdl-ok">&#10004;</span>';
		if (!empty($s['help'])) {
			print '<br><span class="opacitymedium small">'.$langs->trans($s['help']).'</span>';
		}
		print '</td></tr>';
	}
	print '</table>';

	// Conditions d'utilisation
	print '<div class="marginbottomonly margintoponly"><b>'.$langs->trans('NsinfoTradConditions').'</b></div>';
	print '<div>'.$langs->transnoentitiesnoconv('NsinfoTradCond'.ucfirst($current)).'</div>';
	print '<div class="margintoponly">'.nsinfoTradLinksHtml($p, $langs).'</div>';

	print '<div class="margintoponly"><button type="button" class="button" id="tdl-test"><span class="fas fa-vial pictofixedwidth"></span>'.$langs->trans('NsinfoTradTest').'</button> <span id="tdl-test-res"></span></div>';
	print '</div>';
} else {
	// Tableau récapitulatif de tous les fournisseurs pour aider au choix
	print '<div class="tdl-prov">';
	foreach ($providers as $code => $p) {
		print '<div style="--c: '.$p['color'].'"><h3><span class="'.(in_array($code, array('google', 'azure')) ? 'fab' : 'fas').' '.$p['picto'].'"></span>'.dol_escape_htmltag($p['label']).'</h3>'.$langs->transnoentitiesnoconv('NsinfoTradCond'.ucfirst($code)).nsinfoTradLinksHtml($p, $langs).'</div>';
	}
	print '</div>';
}
}

if ($hasfields) {
	nsinfoVerticalTabsEnd();
}

print dol_get_fiche_end();

print '<script>
(function () {
	var token = '.json_encode(newToken()).', url = '.json_encode($_SERVER['PHP_SELF'].'?namemodule='.$namemodule).';
	function post(data) {
		data.token = token;
		return fetch(url, {method: "POST", body: new URLSearchParams(data), credentials: "same-origin"}).then(function (r) { return r.json(); });
	}
	function tick(el) { var ok = (el.closest("td") || el.parentNode).querySelector(".tdl-ok"); if (ok) { ok.style.display = "inline"; setTimeout(function () { ok.style.display = "none"; }, 1000); } }
	var scope = document.getElementById("tdl-scope");
	if (scope) {
		scope.addEventListener("change", function () {
			post({action: "setconst", name: '.json_encode('NSINFO_TRAD_SCOPE_'.strtoupper($namemodule)).', value: scope.value}).then(function () { tick(scope); setTimeout(function () { location.reload(); }, 500); });
		});
	}
	var prov = document.getElementById("tdl-provider");
	if (prov) prov.addEventListener("change", function () {
		post({action: "setconst", name: '.json_encode($prefix.'PROVIDER').', value: prov.value}).then(function () { tick(prov); setTimeout(function () { location.reload(); }, 500); });
	});
	document.querySelectorAll(".tdl-set").forEach(function (el) {
		el.addEventListener("change", function () { post({action: "setconst", name: el.dataset.name, value: el.value}).then(function () { tick(el); }); });
	});
	document.querySelectorAll(".tdl-pos-tile").forEach(function (tile) {
		function choose() {
			document.querySelectorAll(".tdl-pos-tile").forEach(function (o) { o.classList.toggle("on", o === tile); o.setAttribute("aria-checked", o === tile ? "true" : "false"); });
			post({action: "setconst", name: tile.dataset.name, value: tile.dataset.value}).then(function () { tick(tile); });
		}
		tile.addEventListener("click", choose);
		tile.addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); choose(); } });
	});
	var fieldBoxes = document.querySelectorAll(".tdl-field");
	fieldBoxes.forEach(function (c) {
		c.addEventListener("change", function () {
			var v = []; fieldBoxes.forEach(function (o) { if (o.checked) { v.push(o.value); } });
			post({action: "setconst", name: '.json_encode(nsinfoTradFieldsConst()).', value: v.join(",")}).then(function () { tick(c); });
		});
	});
	var col = document.querySelector(".tdl-color"), samples = document.querySelectorAll(".tdl-sample");
	if (col) {
		col.addEventListener("input", function () { samples.forEach(function (i) { i.style.color = col.value; }); });
		document.getElementById("tdl-color-reset").addEventListener("click", function (e) {
			e.preventDefault();
			col.value = "#2e7d32";
			samples.forEach(function (i) { i.style.color = col.value; });
			post({action: "setconst", name: col.dataset.name, value: ""}).then(function () { tick(col); });
		});
	}
	var t = document.getElementById("tdl-test");
	if (t) {
		t.addEventListener("click", function () {
			var r = document.getElementById("tdl-test-res");
			r.textContent = "...";
			post({action: "testtrad"}).then(function (d) {
				r.style.color = d.error ? "#C62828" : "#2E7D32";
				r.textContent = d.error ? d.error : "✔ " + d.text;
			}).catch(function () { r.style.color = "#C62828"; r.textContent = "Error"; });
		});
	}
})();
</script>';

llxFooter();
$db->close();
