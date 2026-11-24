<?php
/* Copyright (C) 2026 Nicolas SILOBRE <contact@ns-info.fr>
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
 * \file    nsinfo/admin/modules_nsinfo.php
 * \ingroup nsinfo
 * \brief   Liste des modules NS INFO installés : activation, désactivation et rechargement (désactiver puis réactiver), un par un ou tous en un clic.
 */

// Charge l'environnement Dolibarr
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
// Bibliothèques utilisées par les init() des modules (dolCopyDir, etc.), chargées comme dans la liste des modules de Dolibarr
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
dol_include_once('/nsinfo/lib/nsinfo.lib.php');
dol_include_once('/nsinfo/lib/setup.lib.php');
dol_include_once('/nsinfo/class/modhistory.class.php');

$langs->loadLangs(array("errors", "admin", "nsinfo@nsinfo"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$modparam = GETPOST('mod', 'aZ09');
// Sous-onglet vertical : modules activés (on) ou désactivés (off)
$sub = GETPOST('sub', 'aZ09') == 'off' ? 'off' : 'on';

/**
 * Modules NS INFO présents dans les dossiers des modules externes (famille ou éditeur « NS INFO » dans le descripteur)
 *
 * @return array<string,array{class:string,file:string,dir:string}>	Classe du descripteur => informations, triées par dossier
 */
function nsinfoInstalledModules()
{
	$list = array();
	foreach (dolGetModulesDirs() as $dir) {
		if (strpos($dir, DOL_DOCUMENT_ROOT.'/core/modules') === 0) {
			continue;	// modules de Dolibarr
		}
		foreach ((array) glob($dir.'mod*.class.php') as $file) {
			$src = @file_get_contents($file, false, null, 0, 20000);
			if ($src === false || !preg_match('/\$this->(family|editor_name)\s*=\s*[\'"]NS INFO[\'"]/', $src)) {
				continue;
			}
			$class = basename($file, '.class.php');
			if (isset($list[$class])) {
				continue;	// même classe dans un autre dossier : Dolibarr prend la première trouvée
			}
			$list[$class] = array('class' => $class, 'file' => $file, 'dir' => basename(dirname(dirname(dirname($file)))));
		}
	}
	uasort($list, function ($a, $b) {
		return ($a['class'] == 'modNsinfo' ? -1 : ($b['class'] == 'modNsinfo' ? 1 : strcmp($a['dir'], $b['dir'])));
	});
	return $list;
}

/**
 * Le module est-il activé ?
 *
 * @param	DolibarrModules	$objMod	Descripteur
 * @return	bool
 */
function nsinfoModuleIsOn($objMod)
{
	return (bool) getDolGlobalString(!empty($objMod->const_name) ? $objMod->const_name : 'MAIN_MODULE_'.strtoupper($objMod->name));
}

/**
 * Désactive puis réactive un module (menus, droits, constantes, hooks rechargés) sans toucher aux modules qui en dépendent
 *
 * @param	string	$class	Classe du descripteur (modXxx)
 * @return	string			Message d'erreur ou ''
 */
function nsinfoReloadModule($class)
{
	global $conf;

	$err = unActivateModule($class, 0);
	if ($err) {
		return $err;
	}
	// unActivateModule() retire la constante MAIN_MODULE_xxx de la base mais pas de $conf : sans ce nettoyage,
	// activateModule() croit le module encore actif et ne fait rien (module laissé désactivé)
	$objMod = new $class($GLOBALS['db']);
	unset($conf->global->{$objMod->const_name});
	try {
		$ret = activateModule($class, 1, 1);
	} catch (Throwable $e) {
		// Erreur PHP dans l'init() du module : signalée, la boucle continue avec les autres modules
		return $e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().') - '.$GLOBALS['langs']->trans('NsinfoModsNotReenabled');
	}
	if (!empty($ret['errors'])) {
		return implode(', ', $ret['errors']);
	}
	// Contrôle : le module doit être de nouveau actif en base
	$db = $GLOBALS['db'];
	$resql = $db->query("SELECT value FROM ".MAIN_DB_PREFIX."const WHERE name = '".$db->escape($objMod->const_name)."' AND entity IN (0, ".((int) $conf->entity).")");
	if (!$resql || !$db->num_rows($resql)) {
		return $GLOBALS['langs']->trans('NsinfoModsNotReenabled');
	}
	Modhistory::set($db, $GLOBALS['user'], $class, array('date_reload' => dol_now()));
	return '';
}

/**
 * Met à jour l'historique des modules (table llx_nsinfo_modhistory) :
 * - module encore inconnu : version et date de dernière activation (installation) relevées ;
 * - version différente de la dernière vue : date de mise à jour = maintenant ;
 * - activation plus récente que la dernière installation et que la dernière réactivation d'ici (module activé depuis la liste
 *   des modules de Dolibarr) : date d'installation = date de cette activation.
 *
 * @param	DolibarrModules[]	$objs	Descripteurs (classe => objet)
 * @return	array<string,array{version:string,date_install:int|string,date_update:int|string,date_reload:int|string}>	Historique à jour
 */
function nsinfoModsSyncHistory($objs)
{
	global $db, $user;

	$hist = Modhistory::getAll($db);
	foreach ($objs as $class => $objMod) {
		$version = (string) $objMod->getVersion(0);
		$lastact = 0;
		if (nsinfoModuleIsOn($objMod) && method_exists($objMod, 'getLastActivationInfo')) {
			$info = $objMod->getLastActivationInfo();
			$lastact = empty($info['lastactivationdate']) ? 0 : (int) $info['lastactivationdate'];
		}
		$values = array();
		if (!isset($hist[$class])) {
			$values = array('version' => $version, 'date_install' => $lastact);
		} else {
			if ($version !== '' && $hist[$class]['version'] !== $version) {
				$values['version'] = $version;
				$values['date_update'] = dol_now();
			}
			if ($lastact && $lastact > (int) $hist[$class]['date_install'] + 10 && $lastact > (int) $hist[$class]['date_reload'] + 10) {
				$values['date_install'] = $lastact;
			}
		}
		if ($values && Modhistory::set($db, $user, $class, $values) > 0) {
			$hist[$class] = array_merge(isset($hist[$class]) ? $hist[$class] : array('version' => '', 'date_install' => '', 'date_update' => '', 'date_reload' => ''), $values);
		}
	}
	return $hist;
}

$modules = nsinfoInstalledModules();
$objs = array();
foreach ($modules as $class => $info) {
	include_once $info['file'];
	if (class_exists($class)) {
		$objs[$class] = new $class($db);
	}
}
$hist = nsinfoModsSyncHistory($objs);

/*
 * Actions (jeton obligatoire)
 */
if ($action && in_array($action, array('enable', 'disable', 'reload', 'reloadall', 'enableall'), true)) {
	if (GETPOST('token', 'alpha') !== currentToken()) {
		accessforbidden('Bad value for CSRF token');
	}
	$errors = array();
	$done = 0;
	if (in_array($action, array('enable', 'disable', 'reload'), true)) {
		if (!isset($objs[$modparam])) {
			accessforbidden();
		}
		if ($action == 'enable') {
			$ret = activateModule($modparam);
			if (!empty($ret['errors'])) {
				$errors[] = $objs[$modparam]->getName().' : '.implode(', ', $ret['errors']);
			} else {
				Modhistory::set($db, $user, $modparam, array('date_install' => dol_now()));
				$done++;
			}
		} elseif ($action == 'disable' && $modparam == 'modNsinfo') {
			$errors[] = $langs->trans('NsinfoModsNsinfoRequired');
		} elseif ($action == 'disable') {
			$err = unActivateModule($modparam);
			if ($err) {
				$errors[] = $objs[$modparam]->getName().' : '.$err;
			} else {
				$done++;
			}
		} elseif ($action == 'reload') {
			$err = nsinfoReloadModule($modparam);
			if ($err) {
				$errors[] = $objs[$modparam]->getName().' : '.$err;
			} else {
				$done++;
			}
		}
	} else {
		// Tous les modules NS INFO : rechargement des modules activés (nsinfo en premier), ou activation de ceux qui sont désactivés
		foreach ($objs as $class => $objMod) {
			$on = nsinfoModuleIsOn($objMod);
			if ($action == 'reloadall' && $on) {
				$err = nsinfoReloadModule($class);
			} elseif ($action == 'enableall' && !$on) {
				$ret = activateModule($class);
				$err = empty($ret['errors']) ? '' : implode(', ', $ret['errors']);
				if (!$err) {
					Modhistory::set($db, $user, $class, array('date_install' => dol_now()));
				}
			} else {
				continue;
			}
			if ($err) {
				$errors[] = $objMod->getName().' : '.$err;
			} else {
				$done++;
			}
		}
	}
	if ($done) {
		setEventMessages($langs->trans('NsinfoModsDone', $done), null, 'mesgs');
	}
	if ($errors) {
		setEventMessages($langs->trans('NsinfoModsErrors'), $errors, 'errors');
	}
	header('Location: '.dol_buildpath('/nsinfo/admin/modules_nsinfo.php', 1).'?sub='.$sub);
	exit;
}

/*
 * Affichage
 */
$page_name = "NsinfoSetupPage";
llxHeader('', $langs->trans($page_name).' - '.$langs->trans('NsinfoModsTab'));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'object_nsinfo@nsinfo');

$head = nsinfoAdminPrepareHead();
print dol_get_fiche_head($head, 'modules', $langs->trans("NsinfoModsTab"), -1, "object_nsinfo.png@nsinfo");

$nbon = 0;
foreach ($objs as $objMod) {
	$nbon += nsinfoModuleIsOn($objMod) ? 1 : 0;
}
$self = dol_buildpath('/nsinfo/admin/modules_nsinfo.php', 1).'?sub='.$sub.'&token='.newToken();
$confirmjs = function ($key, $param = '') use ($langs) {
	return ' onclick="return confirm('.dol_escape_htmltag(json_encode($langs->transnoentitiesnoconv($key, $param))).');"';
};

print '<span class="opacitymedium">'.$langs->trans('NsinfoModsHelp', count($objs), $nbon).'</span><br><br>';

// Recherche et tri de la liste (comme les listes de Dolibarr)
$search_name = GETPOST('search_name', 'alphanohtml');
$search_version = GETPOST('search_version', 'alphanohtml');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = strtoupper(GETPOST('sortorder', 'aZ09')) == 'DESC' ? 'DESC' : 'ASC';
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search_name = '';
	$search_version = '';
}
if (!in_array($sortfield, array('name', 'version', 'date_install', 'date_update', 'date_reload'), true)) {
	$sortfield = '';
}

/**
 * Filtre et trie une liste de modules selon la recherche et le tri demandés
 *
 * @param	DolibarrModules[]	$list	Descripteurs (classe => objet)
 * @return	DolibarrModules[]			Liste filtrée et triée
 */
function nsinfoModsFilterSort($list)
{
	global $search_name, $search_version, $sortfield, $sortorder, $hist, $modules;

	$out = array();
	foreach ($list as $class => $objMod) {
		$haystack = $objMod->getName().' '.$modules[$class]['dir'].' '.$objMod->getDesc();
		if ($search_name !== '' && stripos($haystack, $search_name) === false) {
			continue;
		}
		if ($search_version !== '' && stripos((string) $objMod->getVersion(0), $search_version) === false) {
			continue;
		}
		$out[$class] = $objMod;
	}
	if ($sortfield) {
		uksort($out, function ($a, $b) use ($out, $sortfield, $sortorder, $hist) {
			if ($sortfield == 'name') {
				$cmp = strcasecmp($out[$a]->getName(), $out[$b]->getName());
			} elseif ($sortfield == 'version') {
				$cmp = version_compare((string) $out[$a]->getVersion(0), (string) $out[$b]->getVersion(0));
			} else {
				$cmp = (int) (isset($hist[$a][$sortfield]) ? $hist[$a][$sortfield] : 0) - (int) (isset($hist[$b][$sortfield]) ? $hist[$b][$sortfield] : 0);
			}
			return $sortorder == 'DESC' ? -$cmp : $cmp;
		});
	}
	return $out;
}

/**
 * Affiche une liste de modules (activés ou désactivés) au format des listes de Dolibarr : ligne de recherche, titres triables
 *
 * @param	DolibarrModules[]	$list		Descripteurs à afficher (classe => objet), déjà filtrés et triés
 * @param	bool				$on			true = liste des modules activés (colonne « Recharger »)
 * @param	string				$self		URL de la page avec jeton
 * @param	callable			$confirmjs	Construit l'attribut onclick de confirmation
 * @return	void
 */
function nsinfoModsTable($list, $on, $self, $confirmjs)
{
	global $langs, $hist, $modules, $form, $sub, $search_name, $search_version, $sortfield, $sortorder;

	$url = dol_buildpath('/nsinfo/admin/modules_nsinfo.php', 1);
	$param = '&sub='.urlencode($sub).($search_name !== '' ? '&search_name='.urlencode($search_name) : '').($search_version !== '' ? '&search_version='.urlencode($search_version) : '');
	$nbcol = $on ? 7 : 6;

	print '<form method="GET" action="'.dol_escape_htmltag($url).'" name="nsinfomods">';
	print '<input type="hidden" name="sub" value="'.dol_escape_htmltag($sub).'">';
	print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
	print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
	print '<div class="div-table-responsive">';
	print '<table class="tagtable nobottomiftotal liste">';

	// Ligne de recherche
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth200" name="search_name" value="'.dol_escape_htmltag($search_name).'" placeholder="'.dol_escape_htmltag($langs->trans('Search')).'"></td>';
	print '<td class="liste_titre center"><input type="text" class="flat width50" name="search_version" value="'.dol_escape_htmltag($search_version).'"></td>';
	print '<td class="liste_titre"></td><td class="liste_titre"></td><td class="liste_titre"></td>';
	if ($on) {
		print '<td class="liste_titre"></td>';
	}
	print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons().'</td>';
	print '</tr>';

	// Titres triables
	print '<tr class="liste_titre">';
	print_liste_field_titre('Module', $url, 'name', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Version', $url, 'version', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('NsinfoModsDateInstall', $url, 'date_install', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('NsinfoModsDateUpdate', $url, 'date_update', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre('NsinfoModsDateReload', $url, 'date_reload', '', $param, '', $sortfield, $sortorder, 'center ');
	if ($on) {
		print_liste_field_titre('NsinfoModsReload', $url, '', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre('Status', $url, '', '', $param, '', $sortfield, $sortorder, 'center ');
	print '</tr>';

	if (empty($list)) {
		print '<tr class="oddeven"><td colspan="'.$nbcol.'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
	}
	foreach ($list as $class => $objMod) {
		print '<tr class="oddeven">';
		// Module : picto, nom, puis dossier et description courte en dessous
		print '<td class="tdoverflowmax500">';
		if (!empty($objMod->picto)) {
			print preg_match('/^\//', $objMod->picto) ? img_picto('', $objMod->picto, 'class="pictofixedwidth"', 1) : img_object('', $objMod->picto, 'class="pictofixedwidth"');
		} else {
			print img_object('', 'generic', 'class="pictofixedwidth"');
		}
		print '<strong>'.dol_escape_htmltag($objMod->getName()).'</strong>';
		print '<br><span class="opacitymedium small" title="'.dol_escape_htmltag($objMod->getDesc()).'">'.dol_escape_htmltag($modules[$class]['dir']).' - '.dol_escape_htmltag(dol_trunc($objMod->getDesc(), 70)).'</span></td>';
		print '<td class="center nowraponall">'.dol_escape_htmltag($objMod->getVersion()).'</td>';
		foreach (array('date_install', 'date_update', 'date_reload') as $field) {
			$date = isset($hist[$class][$field]) ? $hist[$class][$field] : '';
			print '<td class="center nowraponall">'.($date ? dol_print_date($date, 'dayhourreduceformat') : '<span class="opacitymedium">-</span>').'</td>';
		}
		if ($on) {
			print '<td class="center"><a class="reposition" href="'.$self.'&action=reload&mod='.urlencode($class).'" title="'.dol_escape_htmltag($langs->trans('NsinfoModsReloadOne')).'">'.nsinfoFaIcon('rotate', '#3879B2').'</a></td>';
		}
		print '<td class="center">';
		if ($class == 'modNsinfo') {
			print img_picto($langs->trans('Enabled'), 'switch_on', 'class="opacitymedium"');
		} elseif ($on) {
			print '<a class="reposition" href="'.$self.'&action=disable&mod='.urlencode($class).'"'.$confirmjs('NsinfoModsDisableConfirm', $objMod->getName()).'>'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
		} else {
			print '<a class="reposition" href="'.$self.'&action=enable&mod='.urlencode($class).'">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
		}
		print '</td>';
		print '</tr>';
	}
	print '</table>';
	print '</div>';
	print '</form>';
}

$form = new Form($db);
$liston = array();
$listoff = array();
foreach ($objs as $class => $objMod) {
	if (nsinfoModuleIsOn($objMod)) {
		$liston[$class] = $objMod;
	} else {
		$listoff[$class] = $objMod;
	}
}

// Sous-onglets verticaux : modules activés / modules désactivés
$url = dol_buildpath('/nsinfo/admin/modules_nsinfo.php', 1);
$vhead = array(
	array($url.'?sub=on', nsinfoFaIcon('toggle-on', '#2E9E5B', 'solid', 'paddingright pictofixedwidth').$langs->trans('NsinfoModsOn').' <span class="opacitymedium">('.count($liston).')</span>', 'on'),
	array($url.'?sub=off', nsinfoFaIcon('toggle-off', '#14182B', 'solid', 'paddingright pictofixedwidth').$langs->trans('NsinfoModsOff').' <span class="opacitymedium">('.count($listoff).')</span>', 'off'),
);
nsinfoVerticalTabsStart($vhead, $sub);
if ($sub == 'on') {
	// Modules activés, avec « Recharger les modules actifs »
	$btn = '<a class="butAction" href="'.$self.'&action=reloadall"'.$confirmjs('NsinfoModsReloadAllConfirm', count($liston)).'>'.nsinfoFaIcon('rotate', '', 'solid', 'paddingright').$langs->trans('NsinfoModsReloadAll').'</a>';
	print load_fiche_titre(nsinfoFaIcon('toggle-on', '#2E9E5B', 'solid', 'paddingright').$langs->trans('NsinfoModsOn').' <span class="opacitymedium">('.count($liston).')</span>', $btn, '');
	nsinfoModsTable(nsinfoModsFilterSort($liston), true, $self, $confirmjs);
} else {
	// Modules désactivés, avec « Activer les modules désactivés »
	$btn = empty($listoff) ? '' : '<a class="butAction" href="'.$self.'&action=enableall"'.$confirmjs('NsinfoModsEnableAllConfirm', count($listoff)).'>'.nsinfoFaIcon('toggle-on', '', 'solid', 'paddingright').$langs->trans('NsinfoModsEnableAll').'</a>';
	print load_fiche_titre(nsinfoFaIcon('toggle-off', '#14182B', 'solid', 'paddingright').$langs->trans('NsinfoModsOff').' <span class="opacitymedium">('.count($listoff).')</span>', $btn, '');
	nsinfoModsTable(nsinfoModsFilterSort($listoff), false, $self, $confirmjs);
}
nsinfoVerticalTabsEnd();

print dol_get_fiche_end();
llxFooter();
$db->close();
