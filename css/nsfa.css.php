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
 * \file    nsinfo/css/nsfa.css.php
 * \ingroup nsinfo
 * \brief   Font Awesome Free 7 du framework NSINFO (classes nsfa-*), chargé uniquement lorsque le Font Awesome de Dolibarr est plus ancien que la version 7 (voir nsinfoFontAwesomeLoaded())
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
dol_include_once('/nsinfo/lib/nsinfo.lib.php');

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

if (nsinfoFontAwesomeLoaded()) {
	$webfonts = dol_buildpath('/nsinfo/fontawesome/webfonts/', 1);
	echo str_replace('../webfonts/', $webfonts, file_get_contents(dol_buildpath('/nsinfo/fontawesome/css/nsfa.css', 0)));
	// Espace entre l'icône et le texte (la largeur de Dolibarr pour pictofixedwidth n'en laisse aucun avec les glyphes de Font Awesome 7)
	echo "\n.nsfa.paddingright, .nsfa.pictofixedwidth { width: auto; min-width: 1.25em; margin-right: 0.45em; }\n";
}
