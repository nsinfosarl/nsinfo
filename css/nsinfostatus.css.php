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
 * \brief   Fichier CSS du module Nsinfo.
 */

//if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Non désactivé car il faut charger la langue personnalisée
//if (!defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Non désactivé. Le code langue est trouvé dans l'URL.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Non désactivé car il faut faire les traductions
//if (!defined('NOCSRFCHECK'))   define('NOCSRFCHECK', 1);		// À désactiver uniquement dans une situation particulière
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // Le fichier doit être accessible depuis la page de connexion, donc sans authentification
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // Nous avons besoin du contenu du menu supérieur
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');
// false ou '' = conserver l'instruction de cache ajoutée par le serveur
// 'public' = supprimer l'instruction de cache ajoutée par le serveur
// et si aucun cache-control n'est ajouté ensuite, un délai de cache par défaut (10800) sera ajouté par PHP.

// Charge l'environnement Dolibarr
$res = 0;
// Essaie main.inc.php dans la racine web connue, définie dans CONTEXT_DOCUMENT_ROOT (pas toujours définie)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Essaie main.inc.php dans la racine web détectée à partir de SCRIPT_FILENAME
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
// Essaie main.inc.php avec un chemin relatif
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

// Charge l'utilisateur pour avoir $user->conf chargé (non fait par défaut ici à cause de la constante NOLOGIN) et charge les droits si on en a besoin dans le CSS
/*if (empty($user->id) && !empty($_SESSION['dol_login'])) {
	$user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Définit le type CSS
header('Content-type: text/css');
// Important : le code suivant sert à mettre ce fichier en cache pour éviter une requête du navigateur à chaque accès à une page Dolibarr.
// Vous pouvez utiliser CTRL+F5 pour rafraîchir le cache du navigateur.
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
   Marqueur fa-statutX seul (affichage « picto » seul, ex. colonne État des objets liés) : pastille de la couleur du statut.
   Masqué quand il accompagne un libellé (le libellé porte alors le badge) ou une référence (.statusref).
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
    display: inline-block !important;
    width: .8em;
    height: .8em;
    border-radius: 50%;
    vertical-align: middle;
    border: 2px solid transparent;
    font-size: 100%;
}

span.hideonsmartphone + span.fa-statut0,
span.hideonsmartphone + span.fa-statut1,
span.hideonsmartphone + span.fa-statut1b,
span.hideonsmartphone + span.fa-statut2,
span.hideonsmartphone + span.fa-statut3,
span.hideonsmartphone + span.fa-statut4,
span.hideonsmartphone + span.fa-statut4b,
span.hideonsmartphone + span.fa-statut5,
span.hideonsmartphone + span.fa-statut6,
span.hideonsmartphone + span.fa-statut7,
span.hideonsmartphone + span.fa-statut8,
span.hideonsmartphone + span.fa-statut9,
span.hideonsmartphone + span.fa-statut10,
.statusref > span.fa-statut0,
.statusref > span.fa-statut1,
.statusref > span.fa-statut1b,
.statusref > span.fa-statut2,
.statusref > span.fa-statut3,
.statusref > span.fa-statut4,
.statusref > span.fa-statut4b,
.statusref > span.fa-statut5,
.statusref > span.fa-statut6,
.statusref > span.fa-statut7,
.statusref > span.fa-statut8,
.statusref > span.fa-statut9,
.statusref > span.fa-statut10,
span.fa-statut0:has(+ span.hideonsmartphone),
span.fa-statut1:has(+ span.hideonsmartphone),
span.fa-statut1b:has(+ span.hideonsmartphone),
span.fa-statut2:has(+ span.hideonsmartphone),
span.fa-statut3:has(+ span.hideonsmartphone),
span.fa-statut4:has(+ span.hideonsmartphone),
span.fa-statut4b:has(+ span.hideonsmartphone),
span.fa-statut5:has(+ span.hideonsmartphone),
span.fa-statut6:has(+ span.hideonsmartphone),
span.fa-statut7:has(+ span.hideonsmartphone),
span.fa-statut8:has(+ span.hideonsmartphone),
span.fa-statut9:has(+ span.hideonsmartphone),
span.fa-statut10:has(+ span.hideonsmartphone) {
    display: none !important;
}

span.fa-statut0 { background-color: #cbd3d3; border-color: #cbd3d3; }
span.fa-statut1 { background-color: #bc9526; border-color: #bc9526; }
span.fa-statut1b { background-color: #bc9526; border-color: #bc9526; }
span.fa-statut2 { background-color: #9c9c26; border-color: #9c9c26; }
span.fa-statut3 { background-color: #bca52b; border-color: #bca52b; }
span.fa-statut4 { background-color: #25a580; border-color: #25a580; }
span.fa-statut4b { background-color: #25a580; border-color: #25a580; }
span.fa-statut5 { background-color: #cad2d2; border-color: #cad2d2; }
span.fa-statut6 { background-color: #cad2d2; border-color: #cad2d2; }
span.fa-statut7 { background-color: #25a580; border-color: #25a580; }
span.fa-statut8 { background-color: #994013; border-color: #994013; }
span.fa-statut9 { background-color: #e7f0f0; border-color: #e7f0f0; }
span.fa-statut10 { background-color: #993013; border-color: #993013; }

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
