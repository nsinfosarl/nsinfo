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

div.mainmenu.nsinfo::before {
	content: "\f249";
}
div.mainmenu.nsinfo {
	background-image: none;
}

.myclasscss {
	/* ... */
}
.tablens {
	table-layout: fixed;
	width: 100%;
	border-collapse: collapse;
	border: 1px solid #523280;
}
.nstabletitle {
	font-weight: bold;
	opacity: 0.8;
	height: 28px;
	background: var(--colorbacktitle1) !important;
}

/* ------------------------------------------------------------------
 * NSINFO media gallery + confirmation popup
 * Without these rules, .modal-active / inline display toggles set by
 * js/modules/{modal,mediaGallery}.js have nothing to switch "into":
 * the elements stay in the normal page flow instead of overlaying it.
 * ------------------------------------------------------------------ */
.hidden {
	display: none !important;
}

.card__confirmation {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.2);
	z-index: 1100;
	display: flex;
	align-items: center;
	justify-content: center;
}
.card__confirmation .confirmation-container {
	width: 100%;
	max-width: 460px;
	background: #fff;
	box-shadow: 0 0 40px 0 rgba(0, 0, 0, 0.1);
	padding: 1.5em;
	text-align: center;
	border-radius: 10px;
}
.card__confirmation .confirmation-icon {
	font-size: 60px;
}
.card__confirmation .confirmation-title {
	font-size: 18px;
	font-weight: 600;
	margin: 1em 0;
}
.card__confirmation .confirmation-close-button {
	display: flex;
	justify-content: flex-end;
	color: rgba(0, 0, 0, 0.3);
	cursor: pointer;
}
.card__confirmation .wpeo-button {
	margin: 0.3em;
}

.wpeo-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	z-index: 1000;
	background: rgba(39, 42, 53, 0.85);
	opacity: 0;
	pointer-events: none;
	transition: opacity 0.2s ease-out;
}
.wpeo-modal.modal-active {
	opacity: 1;
	pointer-events: auto;
	z-index: 1002;
}
.wpeo-modal .modal-container {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 100%;
	max-width: 860px;
	max-height: 80vh;
	overflow-y: auto;
	background: #fff;
	padding: 1em;
	border-radius: 6px;
}
.wpeo-modal .modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 0.5em;
}
.wpeo-modal .modal-close {
	cursor: pointer;
	color: rgba(0, 0, 0, 0.4);
}
.wpeo-modal .modal-footer {
	display: flex;
	justify-content: flex-end;
	align-items: center;
	gap: 0.5em;
	margin-top: 0.5em;
}

.wpeo-button {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	border-radius: 4px;
	padding: 0.4em 0.8em;
	margin: 0.2em;
}
.wpeo-button.button-square-50 {
	width: 50px;
	height: 50px;
	padding: 0;
}
.wpeo-button.button-blue {
	background: #0b419b;
	color: #fff;
}
.wpeo-button.button-grey {
	background: #e5e5e5;
	color: #333;
}
.wpeo-button.button-red {
	background: #e05353;
	color: #fff;
}
.wpeo-button.button-disable {
	opacity: 0.4;
	pointer-events: none;
}
.media-gallery-favorite.favorite {
	background: #f5b400;
}

.wpeo-gridlayout {
	display: grid;
	gap: 0.5em;
}
.wpeo-gridlayout.grid-3 {
	grid-template-columns: repeat(3, 1fr);
}
.wpeo-gridlayout.grid-5 {
	grid-template-columns: repeat(5, 1fr);
}
@media (max-width: 767px) {
	.wpeo-gridlayout.grid-3,
	.wpeo-gridlayout.grid-5 {
		grid-template-columns: repeat(2, 1fr);
	}
}

.clickable-photo {
	position: relative;
	cursor: pointer;
	border: 3px solid transparent;
}
.clickable-photo.clicked-photo {
	border-color: #0b419b;
}

.wpeo-pagination {
	display: inline-flex;
	list-style: none;
	margin: 0;
	padding: 0;
	gap: 0.2em;
}
.wpeo-pagination .pagination-element {
	padding: 0.2em 0.6em;
	cursor: pointer;
}
.wpeo-pagination .pagination-current {
	font-weight: bold;
	text-decoration: underline;
}

.wpeo-loader {
	position: relative;
}
.wpeo-loader .loader-spin {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(255, 255, 255, 0.6);
}

.linked-medias .linked-medias-list {
	flex-wrap: wrap;
}
.linked-medias .media-container {
	position: relative;
	display: inline-block;
}
.linked-medias .media-container .media-gallery-unlink {
	position: absolute;
	top: -14px;
	right: -14px;
	left: auto;
	width: 28px;
	height: 28px;
	min-width: 0;
	border-radius: 50%;
	z-index: 2;
	font-size: 12px;
}

.photo-btn {
	position: relative;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 56px;
	height: 56px;
	border-radius: 50%;
	background: #0b419b;
	color: #fff;
	cursor: pointer;
	font-size: 28px;
}
.photo-btn::after {
	content: "+";
	position: absolute;
	bottom: -6px;
	left: -6px;
	width: 30px;
	height: 30px;
	border-radius: 50%;
	background: #e05353;
	color: #fff;
	font-size: 22px;
	font-weight: bold;
	line-height: 30px;
	text-align: center;
}


