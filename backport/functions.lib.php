<?php
/* Copyright (C) 2026		Nicolas SILOBRE				<contact@ns-info.fr>
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
 * \file    nsinfo/backport/functions.lib.php
 * \ingroup nsinfo
 * \brief   Functions backported from Dolibarr 23.0.4 (htdocs/core/lib/functions.lib.php),
 *          loaded only when Dolibarr is older than 23 (see backport/backport.inc.php)
 */

if (!function_exists('getDolDefaultContextPage')) {
	/**
	 * Return the default context page string
	 *
	 * @param	string		$s					Page path
	 * @return 	string							Value returned
	 */
	function getDolDefaultContextPage($s)
	{
		return str_replace('_', '', basename(dirname($s)).basename($s, '.php'));
	}
}

if (!function_exists('dolBuildUrl')) {
	/**
	 * Return path of url.
	 *
	 * @param	string							$url				Relative path to file
	 * @param	array<string,int|float|string>	$params     		params for the http query
	 * @param	bool							$addtoken			does we need to add token
	 * @return string												path
	 */
	function dolBuildUrl($url, $params = [], $addtoken = false)
	{
		global $db, $hookmanager;

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		if ((!isset($params['mainmenu']) || empty($params['mainmenu'])) && GETPOSTISSET('mainmenu')) {
			$params = array_merge($params, ['mainmenu' => (GETPOST('mainmenu', 'restricthtml'))]);
		}
		if ((!isset($params['leftmenu'])/*  || empty($params['leftmenu']) */) && GETPOSTISSET('leftmenu')) { // do not fill leftmenu if we have leftmenu=
			$params = array_merge($params, ['leftmenu' => (GETPOST('leftmenu', 'restricthtml'))]);
		}
		$parameters = [
			'path' => &$url,
			'params' => &$params,
			'addtoken' => &$addtoken,
		];
		$hookmanager->executeHooks('buildurl', $parameters);
		if ($addtoken) {
			$params = array_merge($params, ['token' => newToken()]);
		}
		// TODO TO REMOVE
		if (getDolGlobalString('MAIN_DEBUG_DOL_BUILDURL')) {
			$params = array_merge($params, ['debug' => 'debug']);
		}
		if ($params) {
			$url .= '?' . http_build_query($params);
		}

		return $url;
	}
}

if (!function_exists('getCallerInfoString')) {
	/**
	 * Get caller info as a string that can be appended to a log message.
	 *
	 * @return string
	 */
	function getCallerInfoString()
	{
		$backtrace = debug_backtrace();
		$msg = "";
		if (count($backtrace) >= 1) {
			$pos = 1;
			if (count($backtrace) == 1) {
				$pos = 0;
			}
			$trace = $backtrace[$pos];
			if (isset($trace['file'], $trace['line'])) {
				$msg = " From {$trace['file']}:{$trace['line']}.";
			}
		}
		return $msg;
	}
}
