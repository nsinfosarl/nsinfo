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
 * @param $arrayofparameters
 * @return void
 * @throws Exception
 */
function nsinfoSetup($arrayofparameters)
{

    global $langs, $conf, $db;


    $formother = new FormOther($db);
    $form = new Form($db);

    $key = '';

    if (!empty($arrayofparameters)) {
        $desc = array();
        foreach ($arrayofparameters as $key => $desc) {
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
                    require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
                    $formproduct = new FormProduct($db);
//					$tmpobject = $desc['object'];
                    $css = !empty($desc['css']) ? $desc['css'] : 'minwidth200';
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="listsel" />';
                    print '<input type="hidden" name="varlst" value="' . $key . '" />';
//					var_dump(getDolGlobalInt($key));
                    print $formproduct->selectWarehouses(getDolGlobalInt($key), $key, '', 1, 0, 0, '', 0, 0, array(), $css);
                    print '&emsp;';
                    print '</td><td>';
                    print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" />';
                    print '</form>';
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
                    $typesocid = !empty($desk['typecompany']) ? $desk['typecompany'] : 1;
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


                case 'tabdeb' :
                    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<table class="tablens noborder" width="100%">';
                    print '<tr class="nstabletitle">	<td>' . $langs->trans("Parameter") . '</td>
                                                    <td>' . $langs->trans("Value") . '</td>
                                                    <td>&emsp;</td></tr>';
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

                case 'titletab':
                    $titletab = $desc['title'];
                    print load_fiche_titre($langs->transnoentities($titletab), '', '');
                    break;

                case 'titletabdesc':
                    $titledesc = $desc['title_desc'];
                    print $langs->transnoentities($titledesc);
                    break;

                case 'bool' :
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    if (!empty($conf->use_javascript_ajax)) {
                        print ajax_constantonoff($key, '', $conf->entity);
                    } else {
                        if (empty(getDolGlobalInt($key)))
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $key . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        else
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $key . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
                    }
                    print '</td>';
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
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_VISIBLE' . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        else
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_VISIBLE' . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
                    }
                    print '</td>';
                    print '<td>';
                    if (!empty($conf->use_javascript_ajax)) {
                        print ajax_constantonoff($const_prefixe . strtoupper($key) . '_NOTNULL', '', $conf->entity);
                    } else {
                        if (empty(getDolGlobalInt($const_prefixe . strtoupper($key) . '_NOTNULL')))
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_NOTNULL' . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        else
                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $const_prefixe . strtoupper($key) . '_NOTNULL' . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
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
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_' . $key . '&var=' . $key . '&token='.$_SESSION['newtoken'].'">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                    else
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_' . $key . '&var=' . $key . '&token='.$_SESSION['newtoken'].'">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';

                    print '</td>';
                    print '</td><td>';
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
                    print '</td><td>';
                    print '&emsp;';
                    print '</td>';

                    break;

                case 'color' :
                    $txtcolor = !empty(getDolGlobalString($key)) ? colorArrayToHex(explode(',', getDolGlobalString($key))) : colorArrayToHex('0, 0, 0');
                    print '<tr class="oddeven">';
                    print '<td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';
                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="setcolor" />';
                    print '<input type="hidden" name="varcolor" value="' . $key . '" />';
                    print $formother->selectColor($txtcolor, $key);
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

                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->trans($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="setround" />';
                    print '<input type="hidden" name="varround" value="' . $key . '" />';
                    print '<input type="number" step = "' . $step . '" size = "' . $size . '" style = "text-align: right; margin: 0; padding: 0;" dir="rtl" id = "' . $key . '" name = "' . $key . '" min = "' . $desc['min'] . '" max = "' . $desc['max'] . '" value = "' . getDolGlobalString($key) . '">';
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
                    $size = 0;
                    $size = $desc['size'];
                    $typetexte = empty($desc['typetext']) ? 'text' : $desc['typetext'];
                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->transnoentitiesnoconv($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="settxt" />';
                    print '<input type="hidden" name="vartxt" value="' . $key . '" />';
                    print '<input type="' . $typetexte . '" name="' . $key . '" size= "' . $size . '" value="' . getDolGlobalString($key) . '" />';
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

                case "textarea" :
                    $size = 0;
                    $size = $desc['size'];
//					$typetexte = empty($desc['typetext']) ? 'text' : $desc['typetext'];
                    $cols = empty($desc['cols']) ? 55 : $desc['cols'];
                    $rows = empty($desc['rows']) ? 5 : $desc['rows'];

                    print '<tr class="oddeven"><td>';
                    print !empty($desc['Tooltip']) ? $form->textwithpicto($langs->transnoentitiesnoconv($key), $langs->trans($key . '_Tooltip')) : $langs->transnoentitiesnoconv($key);
                    print '</td>';

                    print '<td>';
                    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" style="display: inline;">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="settxt" />';
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
                    $rootfile = $desc['rootfile'];

                    print '<center><a href="' . $rootfile . '" target="_blank">' . $langs->transnoentities($key) . '</a></center>';
                    break;

                case "sellist" :
                    $rootfile = $desc['rootfile'];
                    $rootlib = $desc['rootlib'];
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

                case "multisellist" :
                    $rootfile = $desc['rootfile'];
                    $rootlib = $desc['rootlib'];
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
                    $rootfile = $desc['rootfile'];
                    $rootlib = $desc['rootlib'];
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

                case "seluser" :
                    $size = !empty($desc['size']) ? $desc['size'] : 0;
                    $arrayval = !empty($desc['arrayval']) ? $desc['arrayval'] : '';
                    $showempty = !empty($desk['showempty']) ? $desk['showempty'] : 1;
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
                    print $form->selectDate(mktime(0,0,0,$seldate[1],$seldate[0],$seldate[2]), $key, 0, 0, 0, "addprop", 1, 1);
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

            print '</tr>';
        }

    } else {
        print '<br>' . $langs->trans("NothingToSetup");
    }

}

function nsinfoAction($action, $var, $value, $vartxt, $varround, $valuetxt, $defaultvalue = '', $varcolor = '', $varlist = '', $vartxtadv = '', $varselectfa = '')
{

    global $db, $conf, $langs;

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
    }
    elseif ($action == 'multilistsel' && !empty($varlist)) {
        $arr = GETPOST($varlist, 'array');
        $res = '';
        foreach ($arr as $item) {
            if (!empty($res)) $res.=',';
            $res .= $item;
        }
        dolibarr_set_const($db, $varlist, $res, 'chaine', 0, '', $conf->entity);
    }
    elseif ($action == 'listsel' && !empty($varlist)) dolibarr_set_const($db, $varlist, GETPOST($varlist, 'int'), 'int', 0, '', $conf->entity);
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


