<?php
/**
 * Copyright (C) 2024 Nicolas SILOBRE <nsilobre@ns-info.fr>
 */

function getGlobalConst($variable, $dolversion = 18)
{
	global $conf;
	$vers8 = 0;
	$var = '';
	version_compare(DOL_VERSION, '18.0.0', '>=') ? $vers8 = 1 : $vers8 = 0;
	if ($vers8 == 1) $var = getDolGlobalInt($variable);
	else $var = $conf->global->$variable;
	return $var;
}

/**
 * Var_dump simple à 2 entrée possible
 * @param $val
 * @param $val2
 * @return void
 */
function vardtest($val, $val2='') {
	if (!empty(getDolGlobalInt('NSINFODEBBUG'))) {

		if (!empty($lib2)) var_dump('val: '.$val.' / val2: '.$val2);
		else var_dump('val: '.$val);
	}
}

/**
 * var_dump + libellé
 * @param $lib
 * @param $val
 * @param $lib2
 * @param $val2
 * @return void
 */
function vard2($lib, $val, $lib2='', $val2='') {
	if (!empty(getDolGlobalInt('NSINFODEBBUG'))) {

		if (!empty($lib2)) var_dump($lib.': '.$val.' / '.$lib2.': '.$val2);
		else var_dump($lib.': '.$val);
	}
}

/**
 * Le module Dolibarr est-il activé ?
 *
 * @param string $module nom du module à vérifier
 * @return int
 */
if (!function_exists('isModEnabled')) {
	function isModEnabled($module)
	{
		global $conf;
		return !empty($conf->$module->enabled);
	}
}


