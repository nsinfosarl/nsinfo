<?php
/* Copyright (C) 2024 Nicolas Silobre <nsilobre@ns-info.fr>
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
 * \file    nsinfo/css/nsinfo.css.php
 * \ingroup nsinfo
 * \brief   CSS file for module Nsinfo.
 */

//if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (!defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
//if (!defined('NOCSRFCHECK'))   define('NOCSRFCHECK', 1);		// Should be disable only for special situation
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && !empty($_SESSION['dol_login'])) {
	$user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

?>
/* ============================================================
   Badges de statut (fa-statut0 à fa-statut10)
   ============================================================ */

/* ------------------------------------------------------------
   Version "libellé" : le <span.hideonsmartphone> précède
   immédiatement le <span.fa-statutX> et reçoit le style du badge.
   ------------------------------------------------------------ */
span.hideonsmartphone:has(+ span.fa-statut0),
span.hideonsmartphone:has(+ span.fa-statut1),
span.hideonsmartphone:has(+ span.fa-statut1b),
span.hideonsmartphone:has(+ span.fa-statut2),
span.hideonsmartphone:has(+ span.fa-statut3),
span.hideonsmartphone:has(+ span.fa-statut4),
span.hideonsmartphone:has(+ span.fa-statut4b),
span.hideonsmartphone:has(+ span.fa-statut5),
span.hideonsmartphone:has(+ span.fa-statut6),
span.hideonsmartphone:has(+ span.fa-statut7),
span.hideonsmartphone:has(+ span.fa-statut8),
span.hideonsmartphone:has(+ span.fa-statut9),
span.hideonsmartphone:has(+ span.fa-statut10) {
    display: inline-block;
    padding: .1em .5em;
    font-size: 90%;
    font-weight: 700;
    line-height: 1.4;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: .25rem;
    border-width: 2px;
    border-style: solid;
    border-color: transparent;
}

span.hideonsmartphone:has(+ span.fa-statut0) {
    color: #999999;
    border-color: #cbd3d3;
}

span.hideonsmartphone:has(+ span.fa-statut1) {
    color: #fff;
    background-color: #bc9526;
}

span.hideonsmartphone:has(+ span.fa-statut1b) {
    color: #9c850b;
    border-color: #bc9526;
}

span.hideonsmartphone:has(+ span.fa-statut2) {
    color: #fff;
    background-color: #9c9c26;
}

span.hideonsmartphone:has(+ span.fa-statut3) {
    color: #9c850b;
    border-color: #bca52b;
}

span.hideonsmartphone:has(+ span.fa-statut4) {
    color: #fff;
    background-color: #25a580;
}

span.hideonsmartphone:has(+ span.fa-statut4b) {
    color: #25a580;
    border-color: #25a580;
}

span.hideonsmartphone:has(+ span.fa-statut5) {
    color: #999999;
    border-color: #cad2d2;
}

span.hideonsmartphone:has(+ span.fa-statut6) {
    color: #777777;
    background-color: #cad2d2;
}

span.hideonsmartphone:has(+ span.fa-statut7) {
    color: #25a580;
    border-color: #25a580;
}

span.hideonsmartphone:has(+ span.fa-statut8) {
    color: #fff;
    background-color: #994013;
}

span.hideonsmartphone:has(+ span.fa-statut9) {
    color: #999999;
    background-color: #e7f0f0;
}

span.hideonsmartphone:has(+ span.fa-statut10) {
    color: #999999;
    border-color: #993013;
}

/* ------------------------------------------------------------
   Masquage des marqueurs fa-statutX (jamais affichés directement)
   ------------------------------------------------------------ */
span.fa-statut0,
span.fa-statut1,
span.fa-statut1b,
span.fa-statut2,
span.fa-statut3,
span.fa-statut4,
span.fa-statut4b,
span.fa-statut5,
span.fa-statut6,
span.fa-statut7,
span.fa-statut8,
span.fa-statut9,
span.fa-statut10 {
    display: none !important;
}

/* ------------------------------------------------------------
   Version "référence" : le marqueur fa-statutX est enfant direct
   d'un <span.statusref> qui reçoit le style du badge.
   ------------------------------------------------------------ */
.statusref:has(> span.fa-statut0),
.statusref:has(> span.fa-statut1),
.statusref:has(> span.fa-statut1b),
.statusref:has(> span.fa-statut2),
.statusref:has(> span.fa-statut3),
.statusref:has(> span.fa-statut4),
.statusref:has(> span.fa-statut4b),
.statusref:has(> span.fa-statut5),
.statusref:has(> span.fa-statut6),
.statusref:has(> span.fa-statut7),
.statusref:has(> span.fa-statut8),
.statusref:has(> span.fa-statut9),
.statusref:has(> span.fa-statut10) {
    display: inline-block;
    padding: .15em .6em;
    font-size: 95%;
    font-weight: 700;
    line-height: 1.4;
    border-radius: .25rem;
    border-width: 2px;
    border-style: solid;
    border-color: transparent;
}

.statusref:has(> span.fa-statut0) {
    color: #999999;
    border-color: #cbd3d3;
}

.statusref:has(> span.fa-statut1) {
    color: #fff;
    background-color: #bc9526;
}

.statusref:has(> span.fa-statut1b) {
    color: #9c850b;
    border-color: #bc9526;
}

.statusref:has(> span.fa-statut2) {
    color: #fff;
    background-color: #9c9c26;
}

.statusref:has(> span.fa-statut3) {
    color: #9c850b;
    border-color: #bca52b;
}

.statusref:has(> span.fa-statut4) {
    color: #fff;
    background-color: #25a580;
}

.statusref:has(> span.fa-statut4b) {
    color: #25a580;
    border-color: #25a580;
}

.statusref:has(> span.fa-statut5) {
    color: #999999;
    border-color: #cad2d2;
}

.statusref:has(> span.fa-statut6) {
    color: #777777;
    background-color: #cad2d2;
}

.statusref:has(> span.fa-statut7) {
    color: #25a580;
    border-color: #25a580;
}

.statusref:has(> span.fa-statut8) {
    color: #fff;
    background-color: #994013;
}

.statusref:has(> span.fa-statut9) {
    color: #999999;
    background-color: #e7f0f0;
}

.statusref:has(> span.fa-statut10) {
    color: #999999;
    border-color: #993013;
}
