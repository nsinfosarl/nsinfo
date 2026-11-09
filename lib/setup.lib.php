<?php
/* Copyright (C) 2021-2024 Nicolas Silobre <nsilobre@ns-info.fr>
 * Lib permettant de mettre en forme les setups
 */

/**
 * @param $elementtype
 * @param $type
 * @return array|int
 * @throws Exception
 * return array()
 */
function nsinfofieldlist($elementtype, $type = '')
{
    global $conf;

    if ($type == 'name') $sql = "SELECT name as id, label as label ";
    else $sql = "SELECT rowid as id, label as label ";
    $sql .= " FROM " . MAIN_DB_PREFIX . "extrafields where elementtype = '" . $elementtype . "' AND entity = " . $conf->entity;
    dol_syslog("NSINFO requête $sql", LOG_DEBUG);
    $liste = nsinfosqlarray($sql, 1);

    return $liste;
}

function listCat($typecat)
{
    global $db;

    $res = array();
    $sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "categorie WHERE type = " . $typecat;
//	$list = nsinfosqlarray($sql);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num && $num) {
            $obj = $db->fetch_object($resql);
            $res[$obj->rowid] = $obj->label;
            $i++;
        }
    }
    $db->free($resql);
    return $res;
}

/**
 *  Execute an sql and send an array of objects
 *  for mode = 1, sql is id => label
 *  mode= 2 renvoie une liste unique d'id sql select xxx as id
 *  mode = 3 une liste d'objets indexé sur une colonne id
 * @param $sql
 * @param $mode
 * @return array|int
 * @throws Exception
 */
function nsinfosqlarray($sql, $mode = 0)
{
    global $db;

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(basename(__FILE__) . "::" . __FUNCTION__ . ":: erreur sql " . $sql, LOG_ERR);
        return -1;
    }
    $list = array();
    while ($obj = $db->fetch_object($resql)) {
        if ($mode == 1) $list[$obj->id] = $obj->label;
        elseif ($mode == 2) $list[] = $obj->id; // obtenir une liste d'ids
        elseif ($mode == 3) $list[$obj->id] = $obj;
        else $list[] = $obj; // classique tableau d'objet
    }
    $db->free($sql);
    return $list;
}

/**
 * @param $id
 * @return string
 */
function nsinfoReturnName($id)
{
    global $db, $conf;

    $result = '';
    $sql = "SELECT name FROM " . MAIN_DB_PREFIX . "extrafields WHERE rowid = " . $id;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $result = $obj->name;
    }
    $db->free($sql);
    return $result;
}

/**
 * Retourne la liste des modèles de mail liée aux commandes client / fournisseur
 * @param $user
 * @param $outputlangs
 * @return array|int
 */
function Listmodelmail($user, $typecmde, $outputlangs)
{
    global $langs, $db;

    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';

    $listemodel = new FormMail($db);
    $result = $listemodel->fetchAllEMailTemplate($typecmde, $user, $outputlangs);

    if ($result > 0) {
        $modelmail_array[0] = '';
        foreach ($listemodel->lines_model as $line) {
            $reg = array();
            if (preg_match('/\((.*)\)/', $line->label, $reg)) {
                $labeltouse = $langs->trans($reg[1]);
            } else {
                $labeltouse = $line->label;
            }

            // We escape the $labeltouse to store it into $modelmail_array.
            $modelmail_array[$line->id] = dol_escape_htmltag($labeltouse);
            if ($line->lang) {
                $modelmail_array[$line->id] .= ' ' . picto_from_langcode($line->lang);
            }
        }
        return $modelmail_array;
    } else return 0;
}

/**
 * Ouvre une mise en page à onglets verticaux : liste des onglets à gauche, contenu à droite
 * (même tableau d'onglets que dol_get_fiche_head : url, libellé HTML, clé) ; à fermer avec nsinfoVerticalTabsEnd()
 *
 * @param	array<int,array{0:string,1:string,2:string}>	$head	Onglets (url, libellé, clé)
 * @param	string											$active	Clé de l'onglet actif
 * @return	void
 */
function nsinfoVerticalTabsStart($head, $active)
{
    static $styled = false;

    if (!$styled) {
        $styled = true;
        print '<style>
.nsinfo-vtabs { display: flex; align-items: flex-start; gap: 20px; margin: 10px 0 20px; }
.nsinfo-vtabs-nav { flex: 0 0 230px; display: flex; flex-direction: column; border: 1px solid var(--colortopbordertitle1, #ddd); border-radius: 10px; overflow: hidden; background: var(--colorbackbody, #fff); }
.nsinfo-vtabs-nav a { display: block; padding: 11px 16px; text-decoration: none !important; color: inherit !important; border-bottom: 1px solid var(--colortopbordertitle1, #ddd); border-left: 4px solid transparent; }
.nsinfo-vtabs-nav a:last-child { border-bottom: 0; }
.nsinfo-vtabs-nav a:hover { background: rgba(85, 56, 133, .08); }
.nsinfo-vtabs-nav a.active { background: rgba(85, 56, 133, .14); border-left-color: #553885; font-weight: bold; }
.nsinfo-vtabs-content { flex: 1 1 auto; min-width: 0; }
@media (max-width: 800px) { .nsinfo-vtabs { flex-direction: column; } .nsinfo-vtabs-nav { flex: none; width: 100%; } }
</style>';
    }
    print '<div class="nsinfo-vtabs"><nav class="nsinfo-vtabs-nav">';
    foreach ($head as $tab) {
        print '<a href="' . dol_escape_htmltag($tab[0]) . '" class="' . ($tab[2] == $active ? 'active' : '') . '">' . $tab[1] . '</a>';
    }
    print '</nav><div class="nsinfo-vtabs-content">';
}

/**
 * Ferme la mise en page à onglets verticaux ouverte par nsinfoVerticalTabsStart()
 *
 * @return	void
 */
function nsinfoVerticalTabsEnd()
{
    print '</div></div>';
}

/**
 * Un réglage a-t-il sa valeur par défaut ? (vrai aussi si le réglage ne déclare pas de 'default')
 *
 * @param	string					$key	Constante
 * @param	array<string,mixed>		$desc	Description du paramètre (clé 'default' : valeur par défaut)
 * @return	bool							True si la valeur en cours est la valeur par défaut
 */
function nsinfoIsDefault($key, $desc)
{
	if (!array_key_exists('default', $desc) || in_array($desc['type'], array('sep', 'tabdeb', 'tabdeb2', 'tabfin', 'hidden', 'titletab', 'titletabdesc', 'link', 'resetdefault'), true)) {
		return true;
	}
	$current = strtolower(trim(getDolGlobalString($key)));
	$default = strtolower(trim((string) $desc['default']));
	if (in_array($desc['type'], array('bool', 'bool2', 'boolall'), true)) {
		$current = ($current === '' ? '0' : $current);
		$default = ($default === '' ? '0' : $default);
	}
	return ($current === $default || ($current === '' && $default === '0'));
}

/**
 * Réglages qui ne sont pas à leur valeur par défaut, rangés par section (une section commence à chaque paramètre de type 'titletab')
 *
 * @param	array<string,array<string,mixed>>	$arrayofparameters	Paramètres de nsinfoSetup()
 * @return	array<int,string[]>									Clés des réglages modifiés, par numéro de section (0 = première section)
 */
function nsinfoDefaultsBySection($arrayofparameters)
{
	$result = array();
	$section = -1;
	foreach ($arrayofparameters as $key => $desc) {
		if ($desc['type'] === 'titletab' || $desc['type'] === 'titletabdesc') {
			$section++;
			$result[$section] = array();
			continue;
		}
		if ($section < 0) {
			$section = 0;
			$result[0] = array();
		}
		if (!nsinfoIsDefault($key, $desc)) {
			$result[$section][] = $key;
		}
	}
	return $result;
}

/**
 * Signal « réglages différents du défaut » d'une page d'administration : nombre de réglages modifiés et picto ↺ pour tout remettre par défaut
 * (action nsinforesetall, traitée par nsinfoAction()). Chaînes vides si tout est par défaut.
 *
 * @param	array<string,array<string,mixed>>	$arrayofparameters	Paramètres de nsinfoSetup()
 * @param	string[]|null						$keys				Limiter à ces réglages (null = tous)
 * @return	string													HTML (vide si tout est par défaut)
 */
function nsinfoDefaultsNotice($arrayofparameters, $keys = null)
{
	global $langs;

	$changed = array();
	foreach (nsinfoDefaultsBySection($arrayofparameters) as $list) {
		$changed = array_merge($changed, $list);
	}
	if ($keys !== null) {
		$changed = array_values(array_intersect($changed, $keys));
	}
	if (!count($changed)) {
		return '';
	}
	$query = array();
	parse_str((string) (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''), $query);
	unset($query['action'], $query['var'], $query['token']);
	$query = array_merge($query, array('action' => 'nsinforesetall', 'token' => newToken()));
	return '<div class="opacitymedium nsinfo-defaults-notice" style="margin: 6px 0 10px;"><span class="fas fa-undo-alt paddingright" style="color: #553885"></span>'
		. dol_escape_htmltag($langs->trans('NsinfoDefaultsDiffer', count($changed))) . ' '
		. '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?' . http_build_query($query) . '">' . dol_escape_htmltag($langs->trans('NsinfoResetAllDefaults')) . '</a></div>';
}

/**
 * Picto « remettre par défaut » d'un réglage : affiché seulement si la valeur en cours diffère de la valeur par défaut déclarée par le module
 * (clé 'default' du paramètre). Un interrupteur éteint n'a pas de constante : il compte pour 0.
 *
 * @param	string					$key	Constante
 * @param	array<string,mixed>		$desc	Description du paramètre (clé 'default' : valeur par défaut)
 * @return	string							HTML (vide si la valeur est la valeur par défaut)
 */
function nsinfoDefaultPicto($key, $desc)
{
	global $langs;

	if (nsinfoIsDefault($key, $desc)) {
		return '';
	}
	$query = array();
	parse_str((string) (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''), $query);
	unset($query['action'], $query['var'], $query['token']);
	$query = array_merge($query, array('action' => 'nsinforeset', 'var' => $key, 'token' => newToken()));
	return ' <a class="reposition nsinfo-default" href="' . $_SERVER['PHP_SELF'] . '?' . http_build_query($query) . '" title="' . dol_escape_htmltag($langs->trans('NsinfoResetDefault')) . '"><span class="fas fa-undo-alt" style="color: #553885"></span></a>';
}

/**
 * @param $arrayofparameters
 * @return void
 * @throws Exception
 */
function nsinfoSetup($arrayofparameters)
{

    global $langs, $conf, $db, $user;


    $formother = new FormOther($db);
    $form = new Form($db);

    $key = '';

    if (!empty($arrayofparameters)) {
        $desc = array();
        foreach ($arrayofparameters as $key => $desc) {
            // Réglage avec valeur par défaut (clé 'default') : la sortie de la ligne est capturée pour y ajouter le picto « remettre par défaut »
            $withdefault = array_key_exists('default', $desc) && !in_array($desc['type'], array('sep', 'tabdeb', 'tabdeb2', 'tabfin', 'hidden', 'titletab', 'titletabdesc', 'link', 'resetdefault'));
            if ($withdefault) {
                ob_start();
            }
            switch ($desc['type']) {
                case 'listfont' :
                    $selected_font = getDolGlobalString('DPERSOPLUS_DEFFONT') !== null ? getDolGlobalString('DPERSOPLUS_DEFFONT') : 'centurygothic';
                    $dirfonts = DOL_DATA_ROOT . '/' . (empty(getDolGlobalInt('MAIN_MODULE_MULTICOMPANY')) || $conf->entity == 1 ? '' : $conf->entity . '/') . 'dpersoplus/fonts';
                    $listfonts = dol_dir_list($dirfonts, 'files');
                    $listfontuse = array();
                    foreach ($listfonts as $font) {
                        $extension = pathinfo($font['name'], PATHINFO_EXTENSION);
                        if ($extension == 'php') {
                            $fontname = pathinfo($font['name'], PATHINFO_FILENAME);
                            include_once($font['fullname']);
                            if ($name != '') $listfontuse[] = array('name' => $name, 'fontname' => $fontname);
                            $name = '';
                        }
                    }
                    for ($i = 0; $i < count($listfontuse); $i++) $selectOptions[$listfontuse[$i]['fontname']] = $listfontuse[$i]['name'];

                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . 'Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="selectfont" />';
                    print '<input type="hidden" name="var" value="' . $key . '" />';
                    // print '<button class = "button" style = "width: 44px; padding: 0px;" type = "submit" value = "testfont" name = "action">'.$langs->trans('InfraSPlusParamTestFont').'</button>
                    print $form->selectarray('defaultfont', $selectOptions, $selected_font, 0, 0, 0, 'style = "width: calc(95% - 48px); padding: 0px; font-size: inherit; cursor: pointer;"');
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '</td>';
                    break;

                case 'warehouse' :
                    // One warehouse, or several with 'multiple' => 1 (ids saved comma separated, like the categories of 'seltags') ; saved at each change
                    // (script at the end of the page, no button) ; 'urlnew' : button + to create a warehouse, which comes back selected (or added)
                    require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
                    $formproduct = new FormProduct($db);
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth200';
                    $urlnew = !empty($desc['urlnew']) ? $desc['urlnew'] : '';
                    $urlnewtoolip = !empty($desc['urlnewtoolip']) ? $desc['urlnewtoolip'] : '';
                    $multiple = !empty($desc['multiple']);

                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    // Drop-down list without the arrow on the right, like the multiple selection (one style for the page)
                    static $noarrowstyle = false;
                    if (!$noarrowstyle) {
                        $noarrowstyle = true;
                        print '<style>
.nsinfo-noarrow .select2-container--default .select2-selection--single .select2-selection__arrow { display: none; }
.nsinfo-noarrow .select2-container--default .select2-selection--single .select2-selection__rendered { padding-right: 8px; }
.nsinfo-noarrow select { -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none; padding-right: 8px; }
/* Même largeur et même position pour la sélection simple et la sélection multiple */
.nsinfo-noarrow .select2-container { width: 300px !important; min-width: 300px; margin: 0 !important; box-sizing: border-box; }
.nsinfo-noarrow .select2-container .select2-selection { box-sizing: border-box; min-height: 28px; }
</style>';
                    }
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="' . ($multiple ? 'multilistsel' : 'listsel') . '" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    // The class is on a container, not on the form : the form of the first row is nested in the one of the table and ignored by the browser
                    print '<span class="nsinfo-noarrow">';
                    if ($multiple) {
                        $formproduct->loadWarehouses('', '', '', false);
                        $warehouselist = array();
                        foreach ($formproduct->cache_warehouses as $whid => $whdesc) {
                            $warehouselist[$whid] = $whdesc['label'];
                        }
                        print img_picto('', 'stock', 'class="pictofixedwidth"');
                        print $form->multiselectarray($key, $warehouselist, array_filter(explode(',', getDolGlobalString($key))), 0, 0, !empty($desc['css']) ? $desc['css'] : 'minwidth300', 0, 0);
                    } else {
                        print img_picto('', 'stock', 'class="pictofixedwidth"');	// même picto que la sélection multiple
                        print $formproduct->selectWarehouses(getDolGlobalInt($key), $key, '', 1, 0, 0, '', 0, 0, array(), $css);
                    }
                    print '</span>';
                    print '&emsp;';
                    // The warehouse created by the button + comes back selected (or added to the list) and saved (__ID__ is replaced by Dolibarr, actions listsel / multiadd of nsinfoAction)
                    if (!empty($urlnew)) print '<a href="' . DOL_URL_ROOT . $urlnew . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=' . ($multiple ? 'multiadd' : 'listsel') . '&varlst=' . $key . '&' . $key . '=__ID__') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';

                    print '</form>';
                    print '</td><td>';
                    print '</td>';
                    break;


                case 'selectformaddress':
                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
                    $formcompany = new FormCompany($db);
                    $tmpobject = $desc['object'];
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="set_selectformaddress" />';
                    print '<input type="hidden" name="varselectfa" value="' . $key . '" />';
                    print $formcompany->selectTypeContact($tmpobject, getDolGlobalInt($key), $key, 'external', 'position', 1, 'minwidth125imp widthcentpercentminusx maxwidth400');
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '</td>';

                    break;

                case 'selectsocid':
                    $typesocid = !empty($desc['typecompany']) ? $desc['typecompany'] : 1;
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth300';
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->select_company(getDolGlobalInt($key), $key, 's.fournisseur=' . $typesocid, 'SelectThirdParty', 0, 0, null, 0, $css);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '</td>';

                    break;

                case 'sep' :
                    $title = $desc['title'];
                    $nbcol = !empty($desc['col']) ? $desc['col'] : 1;
                    $class = !empty($desc['class']) ? ' class="' . $desc['class'] . '"' : '';
                    print '<tr class="nstabletitle">';
                    print '<td colspan="' . $nbcol . '" ' . $class . '>';
                    print $title;
                    print '</td>';
                    print '</tr>';
                    break;

                case 'tabdeb' :
                    $hiddenname = !empty($desc['hidden_name']) ? $desc['hidden_name'] : '';
                    $hiddenvalue = !empty($desc['hidden_value']) ? $desc['hidden_value'] : '';
                    $td1 = !empty($desc['td1']) ? ' style="width: ' . $desc['td1'] . 'px;"' : '';
                    $td2 = !empty($desc['td2']) ? ' style="width: ' . $desc['td2'] . 'px;"' : '';
                    $td3 = !empty($desc['td3']) ? ' style="width: ' . $desc['td3'] . 'px;"' : '';

                    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    if (!empty($hiddenname) && !empty($hiddenvalue)) print '<input type="hidden" name="' . $hiddenname . '" value="' . $hiddenvalue . '" />';
                    print '<table class="tablens noborder" width="100%">';
                    print '<tr class="nstabletitle">	<td' . $td1 . '>' . $langs->trans("Parameter") . '</td>
                                                    <td' . $td2 . '>' . $langs->trans("Value") . '</td>
                                                    <td' . $td3 . '> </td></tr>';
                    break;

                case 'tabdeb2' :
                    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<table class="tablens noborder" width="100%">';
                    print '<tr class="nstabletitle">	<td>' . $langs->trans("Parameter") . '</td>
                                                    <td>' . $langs->trans("Visible") . '</td>
                                                    <td>' . $langs->trans("Obligatoire") . '</td></tr>';
                    break;

                case 'tabfin' :
                    print '</table>';
                    print '</form>';
                    print '<br>';
                    break;

                case 'hidden':
                    $hiddenname = !empty($desc['hidden_name']) ? $desc['hidden_name'] : '';
                    $hiddenvalue = !empty($desc['hidden_value']) ? $desc['hidden_value'] : '';
                    print '<input type="hidden" name="' . $hiddenname . '" value="' . $hiddenvalue . '" />';
                    break;

                case 'titletab':
                    $titletab = $desc['title'];
                    $picto = '';
                    $picto = !empty($desc['picto']) ? $desc['picto'] : 'generic';
                    print load_fiche_titre($langs->transnoentities($titletab), '', $picto);
                    break;

                case 'titletabdesc':
                    $titledesc = $desc['title_desc'];
                    print $langs->transnoentities($titledesc);
                    break;

                case 'bool' :
                    print '<tr class="oddeven">';
                    print '<td>';
                    if (!empty($desc['picto'])) print img_picto('', $desc['picto'], 'class="pictofixedwidth valignmiddle"');	// picto optionnel devant le libellé
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    if (!empty($conf->use_javascript_ajax)) {
                        print ajax_constantonoff($key, '', $conf->entity);
                    } else {
                        if (empty(getDolGlobalInt($key)))
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $key . '&token=' . newToken() . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        else
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $key . '&token=' . newToken() . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
                    }
                    print '</td>';
                    print '<td>';
                    print '&emsp;';
                    print '</td>';
                    break;

                case 'boolall' :
                    // One switch to enable / disable all the boolean constants listed in $desc['keys']
                    // (switch on when all are enabled ; the list is read from $arrayofparameters in nsinfoAction, not from the url)
                    $allon = !empty($desc['keys']);
                    foreach ((array) $desc['keys'] as $boolkey) {
                        if (!getDolGlobalInt($boolkey)) {
                            $allon = false;
                        }
                    }
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . ($allon ? 'boolall_off' : 'boolall_on') . '&var=' . urlencode($key) . '&token=' . newToken() . '">';
                    print img_picto($langs->trans($allon ? "Enabled" : "Disabled"), $allon ? 'switch_on' : 'switch_off');
                    print '</a>';
                    print '</td><td>';
                    print '&emsp;';
                    print '</td>';
                    break;	// La ligne est fermée après le switch, comme pour les autres types

                case 'resetdefault' :
                    // One button that puts back the default value of the constants listed in $desc['keys'] (they are deleted : the code of the
                    // module then uses its default) ; the list is read from $arrayofparameters in nsinfoAction, not from the url
                    print '<tr class="oddeven">';
                    print '<td>';
                    if (!empty($desc['picto'])) print img_picto('', $desc['picto'], 'class="pictofixedwidth valignmiddle"');
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    $resetlabel = !empty($desc['label']) ? $langs->trans($desc['label']) : $langs->trans('NsinfoResetDefault');
                    print '<a class="button button-cancel reposition" href="' . $_SERVER['PHP_SELF'] . '?action=resetdefault&var=' . urlencode($key) . '&token=' . newToken() . '" onclick="return confirm(' . dol_escape_htmltag(json_encode($langs->transnoentitiesnoconv('NsinfoResetDefaultConfirm'))) . ');"><span class="fas fa-undo-alt paddingright"></span>' . dol_escape_htmltag($resetlabel) . '</a>';
                    print '</td><td>';
                    print '&emsp;';
                    print '</td>';
                    break;

                case 'bool2' :
                    $const_prefixe = !empty($desc['const_prefixe']) ? $desc['const_prefixe'] . '_' : '';
//					$key = strtoupper($key);

                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    if (!empty($conf->use_javascript_ajax)) {
                        print ajax_constantonoff($const_prefixe . strtoupper($key) . '_VISIBLE', '', $conf->entity);
                    } else {
                        if (empty(getDolGlobalInt($const_prefixe . strtoupper($key) . '_VISIBLE')))
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_VISIBLE' . '&token=' . newToken() . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        else
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_VISIBLE' . '&token=' . newToken() . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
                    }
                    print '</td>';
                    print '<td>';
                    if (!empty($conf->use_javascript_ajax)) {
                        print ajax_constantonoff($const_prefixe . strtoupper($key) . '_NOTNULL', '', $conf->entity);
                    } else {
                        if (empty(getDolGlobalInt($const_prefixe . strtoupper($key) . '_NOTNULL')))
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_NOTNULL' . '&token=' . newToken() . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        else
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_NOTNULL' . '&token=' . newToken() . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
                    }
                    print '</td>';
                    break;

                case 'bool3' :
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    if (empty(getDolGlobalInt($key)))
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $key . '&token=' . $_SESSION['newtoken'] . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                    else
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $key . '&token=' . $_SESSION['newtoken'] . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';

                    print '</td>';
                    print '<td>';
                    print '&emsp;';
                    print '</td>';
                    break;


                case "rec" :
                    $btupdate = !empty($desc['btupdate']) ? $desc['btupdate'] : 0;
                    print '<tr class="oddeven">';
                    print '<td>';
//					print
                    print '</td>';
                    print '<td>';
                    if (!empty($btupdate)) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="btupdate" />';
                    }
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</td>';
                    print '<td>';
                    print '&emsp;';
                    print '</td>';

                    break;

                case 'color' :
                    // Sans valeur enregistrée : la valeur par défaut du réglage ('default', « r,g,b »), sinon noir
                    $currentcolor = getDolGlobalString($key);
                    if ($currentcolor === '' && !empty($desc['default'])) $currentcolor = $desc['default'];
                    $txtcolor = !empty($currentcolor) ? colorArrayToHex(explode(',', $currentcolor)) : colorArrayToHex('0, 0, 0');
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="setcolor" />';
                    print '<input type="hidden" name="varcolor" value="' . $key . '" />';
                    // Sélecteur de couleur natif (deux clics, enregistrement immédiat au choix de la couleur)
                    print '<input type="color" id="' . $key . '" name="' . $key . '" value="#' . ltrim($txtcolor, '#') . '" style="width: 56px; height: 32px; padding: 2px; border: 1px solid #ccc; border-radius: 6px; background: none; cursor: pointer; vertical-align: middle;" />';
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="reset" />';
                    print '<input type="hidden" name="var" value="' . $key . '" />';
                    print '&emsp;';
                    print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                    print '</form>';
                    print '</td>';
                    break;

                case "round" :
                    $step = !empty($desc['step']) ? $desc['step'] : 'any';
                    $size = !empty($desc['size']) ? $desc['size'] : 4;
                    $min = !empty($desc['min']) ? $desc['min'] : '';
                    $max = !empty($desc['max']) ? $desc['max'] : '';
                    $unit = !empty($desc['unit']) ? ' ' . $desc['unit'] : '';


                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="setround" />';
                    print '<input type="hidden" name="varround" value="' . $key . '" />';
                    print '<input type="number" step = "' . $step . '" size = "' . $size . '" style = "text-align: right; margin: 0; padding: 0;" dir="rtl" id = "' . $key . '" name = "' . $key . '" min = "' . $min . '" max = "' . $max . '" value = "' . getDolGlobalString($key) . '">' . $unit;
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="reset" />';
                    print '<input type="hidden" name="var" value="' . $key . '" />';
                    print '&emsp;';
                    print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                    print '</form>';
                    break;

                case "text" :
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $typetexte = empty($desc['typetext']) ? 'text' : $desc['typetext'];
                    $defaultvalue = !empty($desc['default']) ? 1 : 0;
                    $hiddenname = !empty($desc['hidden_name']) ? $desc['hidden_name'] : '';
                    $hiddenvalue = !empty($desc['hidden_value']) ? $desc['hidden_value'] : '';

                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->transnoentitiesnoconv($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="settxt" />';
                    if (!empty($hiddenname) && !empty($hiddenvalue)) print '<input type="hidden" name="' . $hiddenname . '" value="' . $hiddenvalue . '" />';
                    print '<input type="hidden" name="vartxt" value="' . $key . '" />';
                    print '<input type="' . $typetexte . '" name="' . $key . '" size= "' . $size . '" value="' . dol_escape_htmltag(getDolGlobalString($key, isset($desc['default']) && is_scalar($desc['default']) && $desc['default'] !== true ? (string) $desc['default'] : '')) . '" />';
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="reset" />';
                    print '<input type="hidden" name="var" value="' . $key . '" />';
                    print '&emsp;';
                    if (!empty($defaultvalue)) print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                    print '</form>';
                    print '</td>';
                    break;

                case "textarea" :
                    $size = 0;
                    $size = $desc['size'];
//					$typetexte = empty($desc['typetext']) ? 'text' : $desc['typetext'];
                    $cols = empty($desc['cols']) ? 55 : $desc['cols'];
                    $rows = empty($desc['rows']) ? 5 : $desc['rows'];
                    $hiddenname = !empty($desc['hidden_name']) ? $desc['hidden_name'] : '';
                    $hiddenvalue = !empty($desc['hidden_value']) ? $desc['hidden_value'] : '';


                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->transnoentitiesnoconv($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="settxt" />';
                    if (!empty($hiddenname) && !empty($hiddenvalue)) print '<input type="hidden" name="' . $hiddenname . '" value="' . $hiddenvalue . '" />';
                    print '<input type="hidden" name="vartxtadv" value="' . $key . '" />';
                    print '<textarea name="' . $key . '" cols= "' . $cols . '" rows= "' . $rows . '" value="' . getDolGlobalString($key) . '" />' . getDolGlobalString($key) . '</textarea>';
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
//					print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
//					print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
//					print '<input type="hidden" name="action" value="reset" />';
//					print '<input type="hidden" name="var" value="' . $key . '" />';
//					print '&emsp;';
//					print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
//					print '</form>';
                    print '</td>';
                    break;

                case "textarea2" :
                    $size = 0;
                    $size = $desc['size'];
//					$typetexte = empty($desc['typetext']) ? 'text' : $desc['typetext'];
                    $cols = empty($desc['cols']) ? 55 : $desc['cols'];
                    $rows = empty($desc['rows']) ? 5 : $desc['rows'];
                    $hiddenname = !empty($desc['hidden_name']) ? $desc['hidden_name'] : '';
                    $hiddenvalue = !empty($desc['hidden_value']) ? $desc['hidden_value'] : '';


                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->transnoentitiesnoconv($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="settxt" />';
                    if (!empty($hiddenname) && !empty($hiddenvalue)) print '<input type="hidden" name="' . $hiddenname . '" value="' . $hiddenvalue . '" />';
                    print '<input type="hidden" name="vartxt" value="' . $key . '" />';
                    print '<textarea name="' . $key . '" cols= "' . $cols . '" rows= "' . $rows . '" value="' . getDolGlobalString($key) . '" />' . getDolGlobalString($key) . '</textarea>';
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';

                    print '</td>';
                    break;

                case "textadv" :
                    $size = $desc['size'];
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="settxtadv" />';
                    print '<input type="hidden" name="vartxtadv" value="' . $key . '" />';
                    include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
                    $doleditor = new DolEditor($key, getDolGlobalString($key), '', $size, 'Full', '', false, true, 1, 200, 70);
                    $doleditor->Create();

                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '</td>';
                    break;

                case 'link' :
                    $rootfile = !empty($desc['rootfile']) ? $desc['rootfile'] : '';

                    if (!empty($desc['row'])) {
                        // Line of the table : label (with tooltip) then link ('linklabel' : text of the link, 'picto' : picto before it)
                        $linklabel = !empty($desc['linklabel']) ? $langs->trans($desc['linklabel']) : $rootfile;
                        $nbcol = !empty($desc['col']) ? $desc['col'] - 1 : 2;
                        print '<tr class="oddeven"><td>';
                        print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                        print '</td><td colspan="' . $nbcol . '">';
                        print '<a href="' . $rootfile . '"' . (empty($desc['sametab']) ? ' target="_blank"' : '') . '>' . (!empty($desc['picto']) ? img_picto('', $desc['picto'], 'class="pictofixedwidth"') : '') . dol_escape_htmltag($linklabel) . '</a>';
                        print '</td></tr>';
                    } else {
                        print '<center><a href="' . $rootfile . '" target="_blank">' . $langs->transnoentities($key) . '</a></center>';
                    }
                    break;

                case "sellist" :
                    $rootfile = !empty($desc['rootfile']) ? $desc['rootfile'] : '';
                    $rootlib = !empty($desc['rootlib']) ? $desc['rootlib'] : '';
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $arrayval = !empty($desc['arrayval']) ? $desc['arrayval'] : '';
//					$css = !empty($desc['css']) ? $desc['css'] : '';
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth300';
                    $show_empty = !empty($desc['show_empty']) ? $desc['show_empty'] : 0;

                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    if (!empty($rootfile)) print ' <a href="' . $rootfile . '" target="_blank">' . $rootlib . '</a></>';
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->selectarray($key, $arrayval, getDolGlobalInt($key), $show_empty, 0, 0, '', 0, 0, 0, '', $css);
                    if (!empty($desc['addurl'])) {
                        // Button + at the end of the list : creation of a new element of the list
                        print ' <a class="editfielda marginleftonly" href="' . $desc['addurl'] . '" title="' . dol_escape_htmltag(!empty($desc['addtitle']) ? $desc['addtitle'] : $langs->trans('Add')) . '"><span class="fa fa-plus-circle valignmiddle"></span></a>';
                    }
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }

                    print '</td>';
                    break;

                case "sellistadd" :
                    $rootfile = !empty($desc['rootfile']) ? $desc['rootfile'] : '';
                    $rootlib = !empty($desc['rootlib']) ? $desc['rootlib'] : '';
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $arrayval = !empty($desc['arrayval']) ? $desc['arrayval'] : '';
//					$css = !empty($desc['css']) ? $desc['css'] : '';
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth300';
                    $show_empty = !empty($desc['show_empty']) ? $desc['show_empty'] : 0;
                    $urlnew = !empty($desc['urlnew']) ? $desc['urlnew'] : '';
                    $urlnewtoolip = !empty($desc['urlnewtoolip']) ? $desc['urlnewtoolip'] : '';


                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    if (!empty($rootfile)) print ' <a href="' . $rootfile . '" target="_blank">' . $rootlib . '</a></>';
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->selectarray($key, $arrayval, getDolGlobalInt($key), $show_empty, 0, 0, '', 0, 0, 0, '', $css);
                    print '&emsp;';
                    print '<a href="' . DOL_URL_ROOT . $urlnew . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->trans("AddProject") . '"></span></a>';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }

                    print '</td>';
                    break;

                case "multisellist" :
                    $rootfile = !empty($desc['rootfile']) ? $desc['rootfile'] : '';
                    $rootlib = !empty($desc['rootlib']) ? $desc['rootlib'] : '';
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $arrayval = !empty($desc['arrayval']) ? $desc['arrayval'] : '';
                    $preselect = !empty($desc['preselect']) ? $desc['preselect'] : '';
//					$css = !empty($desc['css']) ? $desc['css'] : '';
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth300';
                    $show_empty = !empty($desc['show_empty']) ? $desc['show_empty'] : 0;
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    if (!empty($rootfile)) print ' <a href="' . $rootfile . '" target="_blank">' . $rootlib . '</a></>';
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="multilistsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->multiselectarray($key, $arrayval, $preselect, 0, 0, $css, 0, 0);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }

                    print '</td>';
                    break;

                case "selliststring" :
                    $rootfile = !empty($desc['rootfile']) ? $desc['rootfile'] : '';
                    $rootlib = !empty($desc['rootlib']) ? $desc['rootlib'] : '';
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $arrayval = !empty($desc['arrayval']) ? $desc['arrayval'] : '';
                    $css = !empty($desc['css']) ? $desc['css'] : '';
                    $show_empty = !empty($desc['show_empty']) ? $desc['show_empty'] : 0;
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    if (!empty($rootfile)) print ' <a href="' . $rootfile . '" target="_blank">' . $rootlib . '</a></>';
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listselstrg" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->selectarray($key, $arrayval, getDolGlobalString($key), $show_empty, 0, 0, '', 0, 0, 0, '', $css);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }

                    print '</td>';
                    break;

                case "selproject":
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $css = !empty($desc['css']) ? $desc['css'] : '';
                    $socid = !empty($desc['socid']) ? $desc['socid'] : '';

                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
                    $formproject = new FormProjets($db);

                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $formproject->select_projects($socid, getDolGlobalInt($key), $key, 16, 0, 1, 1);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';

                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }
                    print '</td>';
                    break;

                case "seltags":
                    // Category (tag) : 'typtag' => type of category (Categorie::TYPE_xxx), 'multiple' => 1 for several categories (saved comma separated)
                    $typtag = !empty($desc['typtag']) ? $desc['typtag'] : 'propal';
                    $multiple = !empty($desc['multiple']);
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth300';
                    $langs->load("categories");
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';

                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="' . ($multiple ? 'multilistsel' : 'listsel') . '" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print img_picto('', 'category', 'class="pictofixedwidth"');
                    if ($multiple) {
                        $selected = array_filter(explode(',', getDolGlobalString($key)));
                        print $form->multiselectarray($key, $form->select_all_categories($typtag, '', '', 64, 0, 3), $selected, 0, 0, $css, 0, 0);
                    } else {
                        print $form->select_all_categories($typtag, getDolGlobalInt($key), $key, 64, 0, 0, 0, $css, 1);
                        print ajax_combobox($key);
                    }
                    if ($user->hasRight('categorie', 'creer')) {
                        // Button + : creation of a category of this type, then back to the setup page
                        $urlnew = DOL_URL_ROOT . '/categories/card.php?action=create&type=' . urlencode($typtag) . '&backtopage=' . urlencode($_SERVER['PHP_SELF']);
                        print ' <a class="editfielda marginleftonly" href="' . $urlnew . '" title="' . dol_escape_htmltag($langs->trans('CreateCat')) . '"><span class="fa fa-plus-circle valignmiddle"></span></a>';
                    }
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';

                    print '</form>';
                    print '</td>';
                    break;

                case "seluser" :
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $arrayval = !empty($desc['arrayval']) ? $desc['arrayval'] : '';
                    $showempty = !empty($desc['showempty']) ? $desc['showempty'] : 1;
                    $css = !empty($desc['css']) ? $desc['css'] : '';
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->select_dolusers(getDolGlobalInt($key), $key, $showempty, '');
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';

                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }
                    print '</td>';
                    break;

                case "selVAT" :
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $css = !empty($desc['css']) ? $desc['css'] : '';
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->load_tva($key, getDolGlobalString($key));
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';

                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }
                    print '</td>';
                    break;


                case "selDate" :
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $css = !empty($desc['css']) ? $desc['css'] : '';
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);

                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="datesel" />';
                    print '<input type="hidden" name="vardate" value="' . $key . '" />';
                    $seldate = explode('/', getDolGlobalString($key));
                    $ts = (count($seldate) === 3 && $seldate[0] && $seldate[1] && $seldate[2])
                        ? mktime(0, 0, 0, (int)$seldate[1], (int)$seldate[0], (int)$seldate[2])
                        : -1;
                    print $form->selectDate($ts, $key, 0, 0, 0, "addprop", 1, 1);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</td>';
                    break;


                case "selproduct" :
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $css = !empty($desc['css']) ? $desc['css'] : '';
                    $filtre = !empty($desc['filtre']) ? $desc['filtre'] : '';
                    $status = !empty($desc['status']) ? $desc['status'] : 1;
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
                    print $form->select_produits(getDolGlobalInt($key), $key, $filtre, 0, 0, $status, 2, '', 0, array(), 0, 1, 0, $css);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';

                    print '</form>';
                    if (!empty($desc['default'])) {
                        print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                        print '<input type="hidden" name="action" value="reset" />';
                        print '<input type="hidden" name="var" value="' . $key . '" />';
                        print '&emsp;';
                        print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                        print '</form>';
                    }
                    print '</td>';
                    break;


                default :
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="set" />';
                    print '<input type="hidden" name="var" value="' . $key . '" />';
                    print '<input type="integer" name="' . $key . '" value="' . getDolGlobalString($key) . '" />';
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="reset" />';
                    print '<input type="hidden" name="var" value="' . $key . '" />';
                    print '&emsp;';
                    print '<input type="submit" class="button" value="' . $langs->trans("SetDefault") . '" />';
                    print '</form>';
                    print '</td>';
            }

            // Fermeture de la ligne, sauf pour les types qui n'en ouvrent pas ou la ferment eux-mêmes
            if (!in_array($desc['type'], array('sep', 'tabdeb', 'tabdeb2', 'tabfin', 'hidden', 'titletab', 'titletabdesc', 'link'))) {
                print '</tr>';
            }
            if ($withdefault) {
                $rowhtml = ob_get_clean();
                $pictohtml = nsinfoDefaultPicto($key, $desc);
                $lastcell = strrpos($rowhtml, '</td>');
                $slot = '<span class="nsinfo-dp" data-key="' . dol_escape_htmltag($key) . '">' . $pictohtml . '</span>';
                echo ($lastcell !== false) ? substr($rowhtml, 0, $lastcell) . $slot . substr($rowhtml, $lastcell) : $rowhtml;
            }
        }

        // Les pictos « remettre par défaut » se mettent à jour après chaque enregistrement (interrupteur ou champ), sans recharger la page
        print '<script' . (function_exists('getNonce') ? ' nonce="' . getNonce() . '"' : '') . '>
jQuery(function($) {
	var timer = null;
	function refreshDefaults() {
		$.get(window.location.href, function(html) {
			var $new = $("<div></div>").append($.parseHTML(html));
			$(".nsinfo-dp").each(function() {
				var key = $(this).data("key");
				var $src = $new.find(".nsinfo-dp").filter(function() { return $(this).data("key") === key; });
				if ($src.length) { $(this).html($src.first().html()); }
			});
		});
	}
	$(document).ajaxComplete(function(e, xhr, settings) {
		if (settings && ((settings.url && settings.url.indexOf("constantonoff.php") >= 0) || settings.type === "POST")) {
			clearTimeout(timer);
			timer = setTimeout(refreshDefaults, 400);
		}
	});
});
</script>';

        // Enregistrement automatique au changement de tous les champs de réglage (texte, nombre, couleur, listes, dates,
        // zones de texte...), sans recharger la page : coche verte en bout de ligne une fois enregistré (le bouton Mettre à jour reste affiché)
        $autoactions = array('settxt', 'settxtadv', 'setround', 'setcolor', 'set', 'listsel', 'multilistsel', 'listselstrg', 'datesel', 'selectfont', 'set_selectformaddress');
        print '<script' . (function_exists('getNonce') ? ' nonce="' . getNonce() . '"' : '') . '>
jQuery(function($) {
	var actions = ' . json_encode($autoactions) . ';
	var title = ' . json_encode($langs->trans("RecordSaved")) . ';
	// Enregistre le formulaire de réglage sans recharger la page et affiche la coche au bout du champ modifié, pendant 1 seconde
	function save($form, $field) {
		if (!$field || !$field.length) {
			$field = $form.find("input, select, textarea").not("[type=hidden], [type=submit], [type=button]").first();
		}
		var $mark = $form.data("nsinfoMark");
		if (!$mark) {
			$mark = $(\'<span class="nsinfo-saved" style="margin-left:8px;font-weight:bold"></span>\');
			$form.data("nsinfoMark", $mark);
		}
		clearTimeout($mark.data("timer"));
		var $anchor = $field.next(".select2, .select2-container").length ? $field.next(".select2, .select2-container") : $field;
		$mark.stop(true, true).show().css("color", "#888").attr("title", "").text("\\u2026").insertAfter($anchor);
		$.ajax({type: "POST", url: $form.attr("action") || window.location.href, data: $form.serialize() + "&nsinfoenrauto=1"})
			.done(function() {
				$mark.css("color", "#2e9e4f").attr("title", title).text("\\u2714");
				$mark.data("timer", setTimeout(function() { $mark.fadeOut(200); }, 1000));
			})
			.fail(function() { $mark.css("color", "#d9534f").text("\\u2716"); });
	}
	function isAuto($form) {
		return actions.indexOf($form.find("input[type=hidden][name=action]").first().val()) >= 0;
	}
	// Pas de bouton « Modifier » : chaque champ s\'enregistre au changement (le bouton « Valeur par défaut » reste)
	var updatelabel = ' . json_encode($langs->transnoentitiesnoconv("Update")) . ';
	// Pas de bouton « Valeur par défaut » non plus : le picto \u21ba devant le réglage (visible quand la valeur diffère du défaut) le remplace
	var defaultlabel = ' . json_encode($langs->transnoentitiesnoconv("SetDefault")) . ';
	$("form").each(function() {
		var $f = $(this);
		if ($f.find("input[type=hidden][name=action]").length && isAuto($f)) {
			$f.find("input[type=submit]").filter(function() { return $(this).val() === updatelabel; }).hide();
		}
		$f.find("input[type=submit]").filter(function() { return $(this).val() === defaultlabel; }).hide();
	});
	// Au changement d\'un champ
	$(document).on("change", "form input, form select, form textarea", function() {
		var form = this.form || $(this).closest("form")[0];
		if (!form || $(this).is("[type=hidden], [type=submit], [type=button]")) return;
		if (isAuto($(form))) save($(form), $(this));
	});
	// Au clic sur Mettre à jour ou à la touche Entrée : même enregistrement, sans rechargement
	$(document).on("submit", "form", function(e) {
		if ($(this).find("input[type=hidden][name=action]").length && isAuto($(this)) && $(this).find("input, select, textarea").not("[type=hidden], [type=submit], [type=button]").length) {
			e.preventDefault();
			e.stopPropagation();
			save($(this));
		}
	});
});
</script>';

    } else {
        print '<br>' . $langs->trans("NothingToSetup");
    }

}

function nsinfoAction($action, $var, $value, $vartxt, $varround, $valuetxt, $defaultvalue = '', $varcolor = '', $varlist = '', $vartxtadv = '', $varselectfa = '', $arrayofparameters = array())
{

    global $db, $conf, $langs;

    // Enregistrement automatique (ajax, sans rechargement) : le message « Enregistré » ne doit pas s'afficher à la page suivante
    // (les messages d'erreur sont conservés)
    if (!empty($action) && $_SERVER['REQUEST_METHOD'] == 'POST' && GETPOST('nsinfoenrauto', 'int')) {
        register_shutdown_function(function () {
            if (isset($_SESSION['dol_events']['mesgs'])) {
                unset($_SESSION['dol_events']['mesgs']);
            }
        });
    }

    // GET requests are not protected by the core CSRF check (POST forms are): require the token for every action
    // that writes a constant ('listsel' is also reached from the return link of a creation card, without token)
    if (!empty($action) && $_SERVER['REQUEST_METHOD'] != 'POST' && $action != 'listsel' && $action != 'multiadd'
        && ($action == 'activate' || $action == 'disable' || $action == 'set' || $action == 'reset' || $action == 'datesel' || $action == 'selectfont'
            || preg_match('/^(set_|del_|set|multilistsel|listselstrg)/', $action))) {
        // Modules built on nsinfo whose links carry no token yet: accepted only when the request comes from a page of the same host
        $sametoken = (GETPOST('token', 'alpha') == currentToken());
        $referer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
        $samehost = (GETPOST('token', 'alpha') === '' && !empty($referer) && isset($_SERVER['HTTP_HOST'])
            && strcasecmp($referer, preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) == 0);
        if (!$sametoken && !$samehost) {
            setEventMessages($langs->trans('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry'), null, 'errors');
            return 0;
        }
    }

    // Tous les réglages remis à leur valeur par défaut (lien de nsinfoDefaultsNotice())
    if ($action == 'nsinforesetall' && count($arrayofparameters)) {
        if (GETPOST('token', 'alpha') != currentToken()) {
            return 0;
        }
        foreach (nsinfoDefaultsBySection($arrayofparameters) as $list) {
            foreach ($list as $resetkey) {
                $resetvalue = (string) $arrayofparameters[$resetkey]['default'];
                if ($resetvalue === '' || $resetvalue === '0') {
                    dolibarr_del_const($db, $resetkey, $conf->entity);
                } else {
                    dolibarr_set_const($db, $resetkey, $resetvalue, 'chaine', 0, '', $conf->entity);
                }
            }
        }
        setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
        return 1;
    }

    // Un réglage remis à sa valeur par défaut (picto devant chaque réglage qui déclare 'default')
    if ($action == 'nsinforeset' && isset($arrayofparameters[$var]) && array_key_exists('default', $arrayofparameters[$var])) {
        if (GETPOST('token', 'alpha') != currentToken()) {
            return 0;
        }
        $resetdefault = (string) $arrayofparameters[$var]['default'];
        if ($resetdefault === '' || $resetdefault === '0') {
            dolibarr_del_const($db, $var, $conf->entity);
        } else {
            dolibarr_set_const($db, $var, $resetdefault, 'chaine', 0, '', $conf->entity);
        }
        setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
        return 1;
    }

    // Type 'boolall' : enable / disable all the boolean constants of the parameter (list read from $arrayofparameters)
    if (($action == 'boolall_on' || $action == 'boolall_off') && !empty($arrayofparameters[$var]['keys']) && $arrayofparameters[$var]['type'] == 'boolall') {
        if (GETPOST('token', 'alpha') != currentToken()) {
            return 0;
        }
        foreach ((array) $arrayofparameters[$var]['keys'] as $boolkey) {
            if ($action == 'boolall_on') {
                dolibarr_set_const($db, $boolkey, '1', 'chaine', 0, '', $conf->entity);
            } else {
                dolibarr_del_const($db, $boolkey, $conf->entity);
            }
        }
        return 1;
    }

    // Type 'resetdefault' : delete the constants of the parameter, so that the module goes back to its default values (list read from $arrayofparameters)
    if ($action == 'resetdefault' && !empty($arrayofparameters[$var]['keys']) && $arrayofparameters[$var]['type'] == 'resetdefault') {
        if (GETPOST('token', 'alpha') != currentToken()) {
            return 0;
        }
        foreach ((array) $arrayofparameters[$var]['keys'] as $resetkey) {
            $resetvalue = isset($arrayofparameters[$resetkey]) && array_key_exists('default', $arrayofparameters[$resetkey]) ? (string) $arrayofparameters[$resetkey]['default'] : '';
            if ($resetvalue === '' || $resetvalue === '0') {
                dolibarr_del_const($db, $resetkey, $conf->entity);
            } else {
                dolibarr_set_const($db, $resetkey, $resetvalue, 'chaine', 0, '', $conf->entity);
            }
        }
        setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
        return 1;
    }

    if ($action == 'activate') dolibarr_set_const($db, $var, "1", 'chaine', 0, '', $conf->entity);
    elseif (preg_match('/set_(.*)/', $action, $reg)) {
        $code = $reg[1];
        $value = (GETPOST($code) ? GETPOST($code) : 1);
        $res = dolibarr_set_const($db, $var, $value, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $errors[] = $db->lasterror();
            // $error++;
        }
    } elseif (preg_match('/del_(.*)/', $action, $reg)) {
        $code = $reg[1];
        $res = dolibarr_del_const($db, $code, $conf->entity);
        if (!($res > 0)) {
            $errors[] = $db->lasterror();
            // $error++;
        }
    } elseif ($action == 'disable') dolibarr_del_const($db, $var, $conf->entity);
    elseif ($action == 'set') dolibarr_set_const($db, $var, $value, 'chaine', 0, '', $conf->entity);
    elseif ($action == 'set_selectformaddress') dolibarr_set_const($db, $varselectfa, GETPOST($varselectfa, 'alpha'), 'chaine', 0, '', $conf->entity);
    elseif ($action == 'settxt') dolibarr_set_const($db, $vartxt, $valuetxt, 'chaine', 0, '', $conf->entity);
    elseif ($action == 'setround') dolibarr_set_const($db, $varround, GETPOST($varround, 'alpha'), 'chaine', 0, '', $conf->entity);
    elseif ($action == 'selectfont') dolibarr_set_const($db, $var, GETPOST('defaultfont', 'alpha'), 'chaine', 0, '', $conf->entity);
    elseif ($action == 'reset') dolibarr_set_const($db, $var, $defaultvalue, 'chaine', 0, '', $conf->entity);
    elseif ($action == 'settxtadv') dolibarr_set_const($db, $vartxtadv, GETPOST($vartxtadv, 'restricthtml'), 'chaine', 0, '', $conf->entity);
    elseif ($action == 'setcolor') {
        $const = implode(', ', colorStringToArray(GETPOST($varcolor, 'alpha')));
        dolibarr_set_const($db, $varcolor, $const, 'chaine', 0, '', $conf->entity);
    } elseif ($action == 'multiadd' && !empty($varlist)) {
        // A warehouse (or other element) just created is added to the selection of a multiple list ; the id must be a warehouse of the entity
        $newid = GETPOSTINT($varlist);
        if ($newid > 0) {
            $resw = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid = " . ((int) $newid) . " AND entity IN (" . getEntity('stock') . ")");
            if ($resw && $db->num_rows($resw) > 0) {
                $cur = array_filter(explode(',', getDolGlobalString($varlist)));
                if (!in_array($newid, $cur)) {
                    $cur[] = $newid;
                    dolibarr_set_const($db, $varlist, implode(',', $cur), 'chaine', 0, '', $conf->entity);
                }
            }
        }
    } elseif ($action == 'multilistsel' && !empty($varlist)) {
        $arr = GETPOST($varlist, 'array');
        $res = '';
        foreach ($arr as $item) {
            if (!empty($res)) $res .= ',';
            $res .= $item;
        }
        dolibarr_set_const($db, $varlist, $res, 'chaine', 0, '', $conf->entity);
    } elseif ($action == 'listsel' && !empty($varlist)) dolibarr_set_const($db, $varlist, GETPOST($varlist, 'int'), 'int', 0, '', $conf->entity);
    elseif ($action == 'listselstrg' && !empty($varlist)) dolibarr_set_const($db, $varlist, GETPOST($varlist, 'alpha'), 'chaine', 0, '', $conf->entity);
    elseif ($action == 'datesel') dolibarr_set_const($db, $var, date(GETPOST($var)), 'date', 0, '', $conf->entity);

    if (!empty($action)) return 1;
    else return 0;
}

/**
 * @param $key
 * @return string
 */
function getColorRgb2hex($key)
{
    global $conf;
    $value = getDolGlobalString($key);
    $rgbarr = explode(',', $value, 3);
    return sprintf("#%02x%02x%02x", $rgbarr[0], $rgbarr[1], $rgbarr[2]);
}

/**
 * @param $table
 * @param $id
 * @return string
 */
function getDictionaryCode($table, $id)
{
    global $db;
    $res = '';
    $sql = "SELECT code FROM " . MAIN_DB_PREFIX . $table . " WHERE rowid = " . $id;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $res = $obj->code;
    }
    $db->free($resql);
    return $res;

}


/**
 * Check user access on current page
 *
 * @param object|bool $permission Permission to access to current page
 * @param object|null $object Object in current page
 * @param bool $allowExternalUser Allow external user to have access at current page
 */
function nsinfo_check_access($permission, object $object = null, bool $allowExternalUser = false)
{
    global $conf, $langs, $user, $moduleNameLowerCase;


    if (empty($moduleNameLowerCase)) {
        $moduleNameLowerCase = 'nsinfo';
    }

    if (!$user->admin) {
        if (!$permission) accessforbidden();
    }

    if (!$allowExternalUser) {
        if ($user->socid > 0) {
            accessforbidden();
        }
    }
    dol_include_once('/nsinfo/lib/phpversif.lib.php');
    if (isModEnabled('multicompany')) {
        if ($object->id > 0) {
            if ($object->entity != $conf->entity) {
                setEventMessage($langs->trans('ChangeEntityRedirection'), 'warnings');
                $urltogo = dol_buildpath('/custom/' . $moduleNameLowerCase . '/' . $moduleNameLowerCase . 'index.php?mainmenu=' . $moduleNameLowerCase, 1);
                header('Location: ' . $urltogo);
                exit;
            }
        }
    }
}

function getVersionModule($namemodule)
{
    global $db;

    $modM = ucfirst($namemodule);
    $nameMod = 'mod' . $modM;
//var_dump($nameMod);
    if ($namemodule == 'factory') {
        dol_include_once('/' . $namemodule . '/core/lib/' . $namemodule . '.lib.php');
    } else {
        dol_include_once('/' . $namemodule . '/lib/' . $namemodule . '.lib.php');
    }
    dol_include_once('/' . $namemodule . '/core/modules/' . $nameMod . '.class.php');

    $modClass = new $nameMod($db);
    $constantLastVersion = !empty($modClass->getVersion()) ? $modClass->getVersion() : 'NC';

    if (getDolGlobalString('NSINFO_VERSIONMODULE')) return 'Version: ' . $constantLastVersion;
    else return '';
//	print 'Version: ' . $constantLastVersion;


}

//function getArrayVAT()
//{
//	global $db, $mysoc;
//
//	$res = array();
//	$sql = "SELECT t.rowid, t.code, t.taux, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
//	$sql .= " FROM " . MAIN_DB_PREFIX . "c_tva as t, " . MAIN_DB_PREFIX . "c_country as c";
//	$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '" . $db->escape($mysoc->country_code) . "'";
//	$sql .= " AND t.active = 1";
//	$resql = $db->query($sql);
//	if ($resql) {
//		$num = $db->num_rows($resql);
//		$i = 0;
//		while ($i < $num && $num) {
//			$obj = $db->fetch_object($resql);
//			$res[][] .= array($obj->rowid, $obj->taux);
//			$i++;
//		}
//	}
//	$db->free($resql);
//	return $res;
//}


