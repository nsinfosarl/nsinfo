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
/* Column titles (Parameter / Value) : bold, the td rules of the theme win over the tr */
.nstabletitle td {
	font-weight: bold;
}
/* Separator (type sep with 'class' => 'nssep') : purple line above, light background, purple bold text */
.nstabletitle td.nssep {
	border-top: 2px solid #523280;
	background: rgba(82, 50, 128, 0.08);
	color: #523280;
	font-weight: bold;
	padding-top: 6px;
	padding-bottom: 6px;
}
tr.nstabletitle:has(td.nssep) {
	opacity: 1;
}

/* ------------------------------------------------------------------
 * NSINFO media gallery + confirmation popup
 * Without these rules, .modal-active / inline display toggles set by
 * js/modules/{modal,mediaGallery}.js have nothing to switch "into":
 * the elements stay in the normal page flow instead of overlaying it.
 * ------------------------------------------------------------------ */
/*.hidden {*/
/*	display: none !important;*/
/*}*/

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
	bottom: -4px;
	left: -4px;
	width: 18px;
	height: 18px;
	border-radius: 50%;
	background: #7ec8e3;
	color: #fff;
	font-size: 14px;
	font-weight: bold;
	line-height: 18px;
	text-align: center;
}

/* Zone de dépôt de fichiers (nsinfoDropZone, js/modules/dropZone.js) */
.nsinfo-dropzone {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	min-height: 60px;
	padding: 12px;
	border: 2px dashed rgba(0, 0, 0, 0.25);
	border-radius: 6px;
	background-color: rgba(0, 0, 0, 0.02);
	cursor: pointer;
	text-align: center;
	transition: background-color 0.15s, border-color 0.15s;
}
.nsinfo-dropzone:hover,
.nsinfo-dropzone:focus,
.nsinfo-dropzone.nsinfo-dropzone-over {
	border-color: var(--colortextlink, #263c5c);
	background-color: rgba(38, 60, 92, 0.06);
	outline: none;
}
.nsinfo-dropzone-icon {
	font-size: 1.8em;
	opacity: 0.6;
}
.nsinfo-dropzone.nsinfo-dropzone-sending {
	cursor: progress;
	opacity: 0.6;
}
.nsinfo-dropzone.nsinfo-dropzone-disabled {
	cursor: not-allowed;
	opacity: 0.4;
}

/* Groupes de menus déroulants (nsinfoMenuGroupParent / nsinfoMenuGroupChild, js/modules/menu.js) */
.nsinfo-menugroup-toggle {
	float: right;
	cursor: pointer;
	padding: 4px 2px 4px 8px;
	margin-right: 10px;	/* flèche rentrée à l'intérieur du menu */
	opacity: 0.7;
}
.nsinfo-menugroup-toggle:hover {
	opacity: 1;
}
.nsinfo-menugroup-sub {
	margin: 2px 0 4px 6px;
	padding-left: 6px;
	border-left: 2px solid rgba(0, 0, 0, 0.15);
}
div.nsinfo-menugroup-sub div.nsinfo-menugroup-child-block {
	border: none !important;
	background: none;
	padding: 0 !important;
	margin: 0;
}
/* Menu réduit à ses icônes : entrées de même largeur, centrées dans le menu, icône au milieu */
#id-left.nsinfo-menu-minimized div.blockvmenu,
#id-left.nsinfo-menu-minimized div.blockvmenufirst,
#id-left.nsinfo-menu-minimized div.blockvmenulast,
#id-left.nsinfo-menu-minimized div.blockvmenupair,
#id-left.nsinfo-menu-minimized div.blockvmenuimpair {
	box-sizing: border-box;
	width: 24px !important;	/* même largeur pour toutes les entrées : icône de 16 px + 4 px de chaque côté */
	padding-left: 4px !important;
	padding-right: 4px !important;
	margin-left: auto !important;	/* entrées centrées dans le menu */
	margin-right: auto !important;
}
#id-left.nsinfo-menu-minimized div.blockvmenu div.menu_titre {
	width: auto !important;
	text-align: center !important;
}
#id-left.nsinfo-menu-minimized div.menu_titre a.vmenu,
#id-left.nsinfo-menu-minimized div.menu_titre span.vmenu,
#id-left.nsinfo-menu-minimized div.menu_titre span.vmenudisabled {
	margin-left: 0 !important;	/* marge gauche du thème : l'icône serait décalée vers la droite */
	margin-right: 0 !important;
}
#id-left.nsinfo-menu-minimized div.menu_titre .pictofixedwidth {
	width: 16px !important;	/* largeur de l'icône seule : le texte est masqué */
	text-align: center !important;
	padding-right: 0 !important;
	margin-right: 0 !important;
}

/* Menu réduit à ses icônes : seules les entrées principales restent, groupes masqués et sans flèche */
#id-left.nsinfo-menu-minimized .nsinfo-menugroup-toggle,
#id-left.nsinfo-menu-minimized .nsinfo-menugroup-sub {
	display: none !important;
}
<?php if (getDolGlobalInt('NSINFO_MENUMINIMIZED_SUBMENU')) { ?>
/* Option « Menu réduit : afficher les entrées des menus déroulants » : icônes des entrées des groupes affichées, sans retrait */
#id-left.nsinfo-menu-minimized .nsinfo-menugroup-sub {
	display: block !important;
	margin: 0;
	padding: 0;
	border: none;
}
/* Groupe rangé dans le bloc de son entrée parente : entrées à la largeur de ce bloc */
#id-left.nsinfo-menu-minimized .nsinfo-menugroup-sub:not(.nsinfo-menugroup-outside) div.nsinfo-menugroup-child-block {
	width: auto !important;
	padding-left: 0 !important;
	padding-right: 0 !important;
	margin-left: 0 !important;
	margin-right: 0 !important;
}
<?php } ?>
/* Calendar of a period between two dates (js/modules/rangePicker.js) */
.nsinfo-cal { position: absolute; z-index: 2000; background: #fff; color: #1f2937; border: 1px solid #d5dbe3; border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18); padding: 12px 14px; font-size: 0.95em; }
.nsinfo-cal-nav { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
.nsinfo-cal-nav button, .nsinfo-cal-foot button { border: 0; background: transparent; cursor: pointer; border-radius: 8px; padding: 4px 10px; color: #3879B2; font-size: 1.1em; }
.nsinfo-cal-nav button:hover, .nsinfo-cal-foot button:hover { background: #eef3f9; }
.nsinfo-cal-summary { flex: 1; text-align: center; font-size: 0.9em; color: #4b5563; }
.nsinfo-cal-months { display: flex; gap: 22px; }
.nsinfo-cal-title { text-align: center; font-weight: 600; margin-bottom: 4px; }
.nsinfo-cal-grid { display: grid; grid-template-columns: repeat(7, 2.3em); row-gap: 2px; }
.nsinfo-cal-dow { text-align: center; font-size: 0.78em; color: #9ca3af; padding: 3px 0; }
.nsinfo-cal { user-select: none; -webkit-user-select: none; }
.nsinfo-cal-day { touch-action: none; border: 0; background: transparent; height: 2.3em; cursor: pointer; border-radius: 0; color: inherit; font: inherit; position: relative; }
.nsinfo-cal-day:hover { background: #eef3f9; border-radius: 8px; }
.nsinfo-cal-day.off { color: #b6bcc6; }
.nsinfo-cal-day.today { font-weight: 700; box-shadow: inset 0 -3px 0 #3879B2; }
.nsinfo-cal-day.inrange { background: #e3edf8; }
.nsinfo-cal-day.edge { background: #3879B2; color: #fff; font-weight: 600; }
.nsinfo-cal-day.edge.first { border-radius: 8px 0 0 8px; }
.nsinfo-cal-day.edge.last { border-radius: 0 8px 8px 0; }
.nsinfo-cal-day.edge.first.last { border-radius: 8px; }
.nsinfo-cal-foot { display: flex; justify-content: space-between; margin-top: 8px; border-top: 1px solid #eef0f3; padding-top: 6px; }
.nsinfo-period-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: #eef3f9; color: #1f2937; cursor: pointer; border: 1px solid transparent; }
.nsinfo-period-chip:hover { border-color: #3879B2; }
.nsinfo-period-chip .fa { color: #3879B2; }
/* Clock to choose a time (js/modules/timePicker.js) */
.nsinfo-clock { position: absolute; z-index: 2000; background: #fff; color: #1f2937; border: 1px solid #d5dbe3; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18); padding: 14px 16px; user-select: none; -webkit-user-select: none; text-align: center; }
.nsinfo-clock-head { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 2.4em; line-height: 1.1; margin-bottom: 10px; }
.nsinfo-clock-seg { border: 0; background: #eef0f3; border-radius: 10px; padding: 4px 14px; font: inherit; color: inherit; cursor: pointer; }
.nsinfo-clock-seg.on { background: #3879B2; color: #fff; }
.nsinfo-clock-dial { position: relative; margin: 0 auto; border-radius: 50%; background: #eef3f9; touch-action: none; cursor: pointer; }
.nsinfo-clock-hand { position: absolute; left: 0; top: 0; pointer-events: none; }
.nsinfo-clock-hand line { stroke: #3879B2; stroke-width: 2; }
.nsinfo-clock-hand circle { fill: #3879B2; }
.nsinfo-clock-hand circle.tip { fill: #3879B2; }
.nsinfo-clock-hand text { fill: #fff; font-size: 13px; text-anchor: middle; }
.nsinfo-clock-n { position: absolute; width: 34px; height: 34px; margin: -17px 0 0 -17px; line-height: 34px; text-align: center; border-radius: 50%; pointer-events: none; font-size: 0.95em; }
.nsinfo-clock-n.in { font-size: 0.8em; color: #6b7280; }
.nsinfo-clock-n.sel { color: #fff; }
.nsinfo-clock-foot { display: flex; align-items: center; gap: 4px; margin-top: 10px; }
.nsinfo-clock-sp { flex: 1; }
.nsinfo-clock-foot button { border: 0; background: transparent; cursor: pointer; border-radius: 8px; padding: 6px 10px; color: #3879B2; font: inherit; }
.nsinfo-clock-foot button:hover { background: #eef3f9; }
.nsinfo-clock-foot .nsinfo-clock-ok { font-weight: 600; }
@media (max-width: 640px) {
	/* Mobile : the calendar is a sheet at the bottom of the screen, one month, large touch targets */
	.nsinfo-cal { position: fixed; top: auto !important; left: 0 !important; right: 0; bottom: 0; width: auto; border-radius: 16px 16px 0 0; padding: 14px 12px 18px; max-height: 90vh; overflow-y: auto; }
	.nsinfo-cal-months { display: block; }
	.nsinfo-cal-months .nsinfo-cal-month + .nsinfo-cal-month { display: none; }
	.nsinfo-cal-grid { grid-template-columns: repeat(7, 1fr); row-gap: 4px; }
	.nsinfo-cal-day { height: 2.9em; font-size: 1.05em; }
	.nsinfo-cal-nav button, .nsinfo-cal-foot button { padding: 10px 16px; }
	/* The clock too is a sheet at the bottom of the screen */
	.nsinfo-clock { position: fixed; top: auto !important; left: 0 !important; right: 0; bottom: 0; width: auto; border-radius: 16px 16px 0 0; padding: 14px 12px 18px; }
	.nsinfo-clock-foot button { padding: 10px 16px; }
}
