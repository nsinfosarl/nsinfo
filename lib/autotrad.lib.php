<?php
/* Copyright (C) 2026 Nicolas Silobre <contact@ns-info.fr>
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
 * \file    nsinfo/lib/autotrad.lib.php
 * \ingroup nsinfo
 * \brief   Traduction automatique par API externe (DeepL, Google, Azure, LibreTranslate, MyMemory, Lingva), partagée par les modules NS INFO (page de réglage nsinfo/admin/trad_module.php?namemodule=<module>)
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

/**
 * Module dont les réglages de traduction sont utilisés (le module qui appelle les fonctions de cette bibliothèque). Les réglages sont soit
 * communs à tous les modules (par défaut) ou propres au module (option de la page de réglage, constante NSINFO_TRAD_SCOPE_<MODULE>).
 * À appeler une fois au début de la page / du script ajax d'un module, avant les autres fonctions.
 *
 * @param	string|null	$module		Code du module (null = lit seulement le module courant)
 * @return	string					Code du module courant ('' = aucun, réglages communs)
 */
function nsinfoTradContext($module = null)
{
	static $current = '';

	if ($module !== null) {
		$current = preg_replace('/[^a-z0-9_]/', '', strtolower($module));
	}

	return $current;
}

/**
 * Périmètre imposé par le module : sa fonction <module>TradScope() (fichier /<module>/lib/<module>.lib.php) renvoie 'global' (réglages communs à tous
 * les modules) ou 'module' (réglages propres). Sans cette fonction, le périmètre est choisi dans la page de réglage.
 *
 * @param	string	$module		Code du module ('' = le module courant)
 * @return	string				'global', 'module' ou '' (non imposé)
 */
function nsinfoTradForcedScope($module = '')
{
	$module = $module !== '' ? preg_replace('/[^a-z0-9_]/', '', strtolower($module)) : nsinfoTradContext();
	if ($module === '' || $module === 'nsinfo') {
		return '';
	}
	if (!function_exists($module.'TradScope') && $module != 'factory') {
		dol_include_once('/'.$module.'/lib/'.$module.'.lib.php');
	}
	$scope = function_exists($module.'TradScope') ? call_user_func($module.'TradScope') : '';

	return in_array($scope, array('global', 'module'), true) ? $scope : '';
}

/**
 * Le réglage du module est-il propre (non commun à tous les modules) ?
 * Le module peut imposer son périmètre (nsinfoTradForcedScope()), sinon c'est le choix fait dans la page de réglage.
 *
 * @param	string	$module		Code du module ('' = le module courant)
 * @return	bool
 */
function nsinfoTradIsModuleScope($module = '')
{
	$module = $module !== '' ? preg_replace('/[^a-z0-9_]/', '', strtolower($module)) : nsinfoTradContext();
	if ($module === '' || $module === 'nsinfo') {
		return false;
	}
	$forced = nsinfoTradForcedScope($module);

	return $forced !== '' ? ($forced == 'module') : (getDolGlobalString('NSINFO_TRAD_SCOPE_'.strtoupper($module)) === 'module');
}

/**
 * Préfixe des constantes des réglages : NSINFO_TRAD_ (communs à tous les modules) ou NSINFO_TRAD_<MODULE>_ (propre au module)
 *
 * @param	string	$module		Code du module ('' = le module courant)
 * @return	string
 */
function nsinfoTradPrefix($module = '')
{
	$module = $module !== '' ? preg_replace('/[^a-z0-9_]/', '', strtolower($module)) : nsinfoTradContext();

	return nsinfoTradIsModuleScope($module) ? 'NSINFO_TRAD_'.strtoupper($module).'_' : 'NSINFO_TRAD_';
}

/**
 * Fournisseurs de traduction automatique, avec les réglages dont chacun a besoin et ses conditions d'utilisation
 *
 * Réglages : name (suffixe de la constante NSINFO_TRAD_<PROVIDER>_<NAME>), label (clé de langue), type (text, password,
 * select), required, default, help (clé de langue), options (pour select).
 * Les conditions (quota gratuit, limites, éléments nécessaires) sont des clés de langue NsinfoTrad<Provider>Cond, rédigées à partir de la
 * documentation de chaque fournisseur.
 *
 * @return	array<string,array<string,mixed>>
 */
function nsinfoTradProviders()
{
	return array(
		'deepl' => array(
			'label' => 'DeepL API Free / Pro', 'picto' => 'fa-language', 'color' => '#0F2B46', 'doc' => 'https://developers.deepl.com/docs', 'links' => array(array('NsinfoTradLinkSignup', 'https://www.deepl.com/pro-api'), array('NsinfoTradLinkKeys', 'https://www.deepl.com/your-account/keys')),
			'html' => true,
			'settings' => array(
				array('name' => 'KEY', 'label' => 'NsinfoTradKey', 'type' => 'password', 'required' => 1, 'help' => 'NsinfoTradKeyDeepl'),
				array('name' => 'PLAN', 'label' => 'NsinfoTradPlan', 'type' => 'select', 'required' => 1, 'default' => 'free', 'options' => array('free' => 'DeepL API Free (api-free.deepl.com)', 'pro' => 'DeepL API Pro (api.deepl.com)')),
			),
		),
		'google' => array(
			'label' => 'Google Cloud Translation (Basic v2)', 'picto' => 'fa-google', 'color' => '#4285F4', 'doc' => 'https://cloud.google.com/translate/docs/reference/rest/v2/translate', 'links' => array(array('NsinfoTradLinkEnable', 'https://console.cloud.google.com/apis/library/translate.googleapis.com'), array('NsinfoTradLinkKeys', 'https://console.cloud.google.com/apis/credentials')),
			'html' => true,
			'settings' => array(
				array('name' => 'KEY', 'label' => 'NsinfoTradKey', 'type' => 'password', 'required' => 1, 'help' => 'NsinfoTradKeyGoogle'),
			),
		),
		'azure' => array(
			'label' => 'Microsoft Azure Translator', 'picto' => 'fa-microsoft', 'color' => '#0078D4', 'doc' => 'https://learn.microsoft.com/azure/ai-services/translator/text-translation/reference/v3/translate', 'links' => array(array('NsinfoTradLinkCreate', 'https://portal.azure.com/#create/Microsoft.CognitiveServicesTextTranslation'), array('NsinfoTradLinkPricing', 'https://azure.microsoft.com/pricing/details/cognitive-services/translator/')),
			'html' => true,
			'settings' => array(
				array('name' => 'KEY', 'label' => 'NsinfoTradKey', 'type' => 'password', 'required' => 1, 'help' => 'NsinfoTradKeyAzure'),
				array('name' => 'REGION', 'label' => 'NsinfoTradRegion', 'type' => 'text', 'required' => 0, 'help' => 'NsinfoTradRegionAzure'),
				array('name' => 'URL', 'label' => 'NsinfoTradEndpoint', 'type' => 'text', 'required' => 0, 'default' => 'https://api.cognitive.microsofttranslator.com', 'help' => 'NsinfoTradEndpointAzure'),
			),
		),
		'libretranslate' => array(
			'label' => 'LibreTranslate', 'picto' => 'fa-globe', 'color' => '#1F6FEB', 'doc' => 'https://docs.libretranslate.com', 'links' => array(array('NsinfoTradLinkSignup', 'https://libretranslate.com'), array('NsinfoTradLinkSelfHost', 'https://github.com/LibreTranslate/LibreTranslate')),
			'html' => true,
			'settings' => array(
				array('name' => 'URL', 'label' => 'NsinfoTradEndpoint', 'type' => 'text', 'required' => 1, 'default' => 'https://libretranslate.com', 'help' => 'NsinfoTradEndpointLibre'),
				array('name' => 'KEY', 'label' => 'NsinfoTradKey', 'type' => 'password', 'required' => 0, 'help' => 'NsinfoTradKeyLibre'),
			),
		),
		'mymemory' => array(
			'label' => 'MyMemory', 'picto' => 'fa-brain', 'color' => '#E67E22', 'doc' => 'https://mymemory.translated.net/doc/spec.php', 'links' => array(array('NsinfoTradLinkLimits', 'https://mymemory.translated.net/doc/usagelimits.php')),
			'html' => false,
			'settings' => array(
				array('name' => 'EMAIL', 'label' => 'NsinfoTradEmail', 'type' => 'text', 'required' => 0, 'help' => 'NsinfoTradEmailMymemory'),
			),
		),
		'lingva' => array(
			'label' => 'Lingva Translate', 'picto' => 'fa-comments', 'color' => '#2E7D32', 'doc' => 'https://github.com/thedaviddelta/lingva-translate', 'links' => array(array('NsinfoTradLinkInstances', 'https://github.com/thedaviddelta/lingva-translate#instances')),
			'html' => false,
			'settings' => array(
				array('name' => 'URL', 'label' => 'NsinfoTradEndpoint', 'type' => 'text', 'required' => 1, 'default' => 'https://lingva.ml', 'help' => 'NsinfoTradEndpointLingva'),
			),
		),
	);
}

/**
 * Liens d'un fournisseur (créer le compte / obtenir la clé, documentation), toujours ouverts dans un nouvel onglet
 *
 * @param	array<string,mixed>	$provider	Définition du fournisseur (voir nsinfoTradProviders())
 * @param	Translate			$langs		Translate
 * @return	string				HTML
 */
function nsinfoTradLinksHtml($provider, $langs)
{
	if (!function_exists('nsinfoFaIcon')) {
		dol_include_once('/nsinfo/lib/nsinfo.lib.php');
	}
	$links = isset($provider['links']) ? $provider['links'] : array();
	$links[] = array('NsinfoTradDoc', $provider['doc']);
	$out = '<div class="margintoponly">';
	foreach ($links as $link) {
		$out .= '<div><a href="'.dol_escape_htmltag($link[1]).'" target="_blank" rel="noopener noreferrer">'.nsinfoFaIcon('arrow-up-right-from-square', '#3879B2', 'solid', 'pictofixedwidth').$langs->transnoentitiesnoconv($link[0]).'</a></div>';
	}

	return $out.'</div>';
}

/**
 * Position du picto de traduction défini dans les réglages : right (à côté du champ), top ou bottom (dans le champ, à droite)
 *
 * @return	string	'right', 'top' ou 'bottom'
 */
function nsinfoTradPosition()
{
	$pos = getDolGlobalString(nsinfoTradPrefix().'POSITION');

	return in_array($pos, array('right', 'top', 'bottom')) ? $pos : 'right';
}

/**
 * Nom de la constante des champs qui ont le picto : toujours propre au module (même lorsque les réglages de l'API sont communs à tous les modules)
 *
 * @param	string	$module		Code du module ('' = le module courant)
 * @return	string
 */
function nsinfoTradFieldsConst($module = '')
{
	$module = $module !== '' ? preg_replace('/[^a-z0-9_]/', '', strtolower($module)) : nsinfoTradContext();

	return $module !== '' ? 'NSINFO_TRAD_'.strtoupper($module).'_FIELDS' : 'NSINFO_TRAD_FIELDS';
}

/**
 * Champs du module qui ont le picto de traduction : codes cochés dans les réglages (constante nsinfoTradFieldsConst(), séparés par des virgules).
 * Le module liste ses champs traduisibles avec une fonction <module>TradFields() renvoyant code => libellé ; aucun n'est coché
 * par défaut : le picto n'est affiché que sur les champs demandés.
 *
 * @return	string[]
 */
function nsinfoTradFields()
{
	return array_values(array_filter(array_map('trim', explode(',', getDolGlobalString(nsinfoTradFieldsConst())))));
}

/**
 * Ce champ a-t-il le picto de traduction ? (une API est configurée et le champ est coché dans les réglages)
 *
 * @param	string	$code	Code du champ, tel que donné par <module>TradFields()
 * @return	bool
 */
function nsinfoTradFieldEnabled($code)
{
	return nsinfoTradActiveProvider() !== '' && in_array($code, nsinfoTradFields(), true);
}

/**
 * Couleur du picto de traduction défini dans les réglages (vert par défaut)
 *
 * @return	string	Color #rrggbb
 */
function nsinfoTradColor()
{
	$color = getDolGlobalString(nsinfoTradPrefix().'COLOR');

	return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#2E7D32';
}

/**
 * Style du picto de traduction (à afficher une seule fois dans la page)
 *
 * @return	string	HTML
 */
function nsinfoTradCss()
{
	return '<style>
.nsinfo-trad-wrap { position: relative; display: inline-block; vertical-align: top; max-width: 100%; }
a.nsinfo-trad, a.nsinfo-trad:link, a.nsinfo-trad:visited, a.nsinfo-trad:hover, a.nsinfo-trad .fas { color: '.nsinfoTradColor().' !important; }
.nsinfo-trad { cursor: pointer; text-decoration: none; font-size: 1.15em; line-height: 1; }
.nsinfo-trad:hover { filter: brightness(.8); }
.nsinfo-trad.busy { opacity: .4; pointer-events: none; }
.nsinfo-trad-right { display: inline-flex; align-items: center; }
.nsinfo-trad-right .nsinfo-trad { margin-left: 6px; }
.nsinfo-trad-top .nsinfo-trad, .nsinfo-trad-bottom .nsinfo-trad { position: absolute; right: 8px; }
.nsinfo-trad-top .nsinfo-trad { top: 6px; }
.nsinfo-trad-bottom .nsinfo-trad { bottom: 6px; }
.nsinfo-trad-top > input, .nsinfo-trad-top > textarea, .nsinfo-trad-bottom > input, .nsinfo-trad-bottom > textarea { padding-right: 28px; }
</style>';
}

/**
 * Ouverture de l'enveloppe d'un champ qui a un picto de traduction
 *
 * @return	string	HTML
 */
function nsinfoTradWrapOpen()
{
	return '<span class="nsinfo-trad-wrap nsinfo-trad-'.nsinfoTradPosition().'">';
}

/**
 * Picto de traduction d'un champ (rien d'autre : pas de texte), à afficher après le champ à l'intérieur de nsinfoTradWrapOpen() ... </span>
 *
 * @param	array<string,string|int>	$data	Attributs data (data-xxx) lus par le script de la page
 * @param	string						$title	Titre (info-bulle)
 * @return	string						HTML
 */
function nsinfoTradPictoHtml($data, $title)
{
	$attrs = '';
	foreach ($data as $k => $v) {
		$attrs .= ' data-'.preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $k)).'="'.dol_escape_htmltag((string) $v).'"';
	}

	return '<a href="#" class="nsinfo-trad tdl-trad"'.$attrs.' title="'.dol_escape_htmltag($title).'"><span class="fas fa-language"></span></a>';
}

/**
 * Valeur d'un réglage d'un fournisseur (constante NSINFO_TRAD_<PROVIDER>_<NAME>), ou sa valeur par défaut
 *
 * @param	string	$provider	Code du fournisseur
 * @param	string	$name		Nom du réglage
 * @return	string
 */
function nsinfoTradSetting($provider, $name)
{
	$value = getDolGlobalString(nsinfoTradPrefix().strtoupper($provider).'_'.strtoupper($name));
	if ($value === '') {
		$providers = nsinfoTradProviders();
		foreach ($providers[$provider]['settings'] as $s) {
			if ($s['name'] == $name && isset($s['default'])) {
				return (string) $s['default'];
			}
		}
	}

	return $value;
}

/**
 * Fournisseur choisi dans les réglages, seulement si ses réglages obligatoires sont renseignés
 *
 * @return	string	Code du fournisseur, '' si aucun ou incomplet
 */
function nsinfoTradActiveProvider()
{
	$provider = getDolGlobalString(nsinfoTradPrefix().'PROVIDER');
	$providers = nsinfoTradProviders();
	if (!isset($providers[$provider])) {
		return '';
	}
	foreach ($providers[$provider]['settings'] as $s) {
		if (!empty($s['required']) && nsinfoTradSetting($provider, $s['name']) === '') {
			return '';
		}
	}

	return $provider;
}

/**
 * Index (1 = en_US, 2 = fr_FR, 3 = de_DE, 4 = it_IT, 5 = es_ES) d'une locale, 0 si ce n'est pas une des 5 langues du module
 *
 * @param	string	$locale		Locale (fr_FR, fr_CA, en_GB...)
 * @return	int
 */
function nsinfoTradLangIndex($locale)
{
	$map = array('en' => 1, 'fr' => 2, 'de' => 3, 'it' => 4, 'es' => 5);
	$code = strtolower(substr((string) $locale, 0, 2));

	return isset($map[$code]) ? $map[$code] : 0;
}

/**
 * Langues vers lesquelles le fournisseur des réglages peut traduire (liste demandée à son API, conservée un jour), utilisée par les modules pour proposer le
 * choix d'une langue. Sans fournisseur (ou si son API ne répond pas) : les cinq langues du module.
 *
 * @param	string	$provider	Fournisseur ('' = celui des réglages)
 * @param	bool	$refresh	Ignore la liste conservée
 * @return	array<string,string>	Nom de la langue, par code du fournisseur ; les langues favorites d'abord, puis par nom
 */
function nsinfoTradLanguages($provider = '', $refresh = false)
{
	static $memo = array();

	$base = array('en' => 'English', 'fr' => 'Français', 'de' => 'Deutsch', 'it' => 'Italiano', 'es' => 'Español');
	$provider = $provider !== '' ? $provider : nsinfoTradActiveProvider();
	$providers = nsinfoTradProviders();
	if (!isset($providers[$provider])) {
		return $base;
	}
	$sign = $provider.'|'.nsinfoTradSetting($provider, 'URL').'|'.nsinfoTradSetting($provider, 'PLAN').'|'.md5(nsinfoTradSetting($provider, 'KEY'));
	if (isset($memo[$sign]) && !$refresh) {
		return $memo[$sign];
	}
	$dir = DOL_DATA_ROOT.'/nsinfo/temp';
	$file = $dir.'/trad_languages_'.md5($sign).'.json';
	if (!$refresh && is_readable($file) && filemtime($file) > dol_now() - 86400) {
		$kept = json_decode((string) file_get_contents($file), true);
		if (is_array($kept) && count($kept)) {
			return $memo[$sign] = $kept;
		}
	}

	$list = array();
	$error = '';
	switch ($provider) {
		case 'deepl':
			$host = (nsinfoTradSetting('deepl', 'PLAN') == 'pro') ? 'https://api.deepl.com' : 'https://api-free.deepl.com';
			$data = nsinfoTradDecode(nsinfoTradCall($host.'/v2/languages?type=target', 'GET', '', array('Authorization: DeepL-Auth-Key '.nsinfoTradSetting('deepl', 'KEY')), 0), $error);
			foreach ($data as $row) {
				if (!empty($row['language']) && !empty($row['name'])) {
					$list[(string) $row['language']] = (string) $row['name'];
				}
			}
			break;
		case 'google':
			$data = nsinfoTradDecode(nsinfoTradCall('https://translation.googleapis.com/language/translate/v2/languages?target=en&key='.urlencode(nsinfoTradSetting('google', 'KEY')), 'GET', '', array(), 0), $error);
			foreach ((isset($data['data']['languages']) ? $data['data']['languages'] : array()) as $row) {
				if (!empty($row['language'])) {
					$list[(string) $row['language']] = (string) (isset($row['name']) ? $row['name'] : $row['language']);
				}
			}
			break;
		case 'azure':
			$data = nsinfoTradDecode(nsinfoTradCall('https://api.cognitive.microsofttranslator.com/languages?api-version=3.0&scope=translation', 'GET', '', array('Accept-Language: en'), 0), $error);
			foreach ((isset($data['translation']) ? $data['translation'] : array()) as $code => $row) {
				$list[(string) $code] = (string) (isset($row['name']) ? $row['name'] : $code);
			}
			break;
		case 'libretranslate':
			$data = nsinfoTradDecode(nsinfoTradCall(rtrim(nsinfoTradSetting('libretranslate', 'URL'), '/').'/languages', 'GET', '', array(), 2), $error);
			foreach ($data as $row) {
				if (!empty($row['code'])) {
					$list[(string) $row['code']] = (string) (isset($row['name']) ? $row['name'] : $row['code']);
				}
			}
			break;
		case 'lingva':
			$data = nsinfoTradDecode(nsinfoTradCall(rtrim(nsinfoTradSetting('lingva', 'URL'), '/').'/api/v1/languages/target', 'GET', '', array(), 2), $error);
			foreach ((isset($data['languages']) ? $data['languages'] : array()) as $row) {
				if (!empty($row['code']) && $row['code'] != 'auto') {
					$list[(string) $row['code']] = (string) (isset($row['name']) ? $row['name'] : $row['code']);
				}
			}
			break;
		default:
			// MyMemory n'a pas de liste de langues : les langues usuelles
			$list = $base + array('pt' => 'Português', 'nl' => 'Nederlands', 'pl' => 'Polski', 'ru' => 'Русский', 'ja' => '日本語', 'zh' => '中文', 'ar' => 'العربية', 'sv' => 'Svenska', 'da' => 'Dansk', 'fi' => 'Suomi', 'el' => 'Ελληνικά', 'tr' => 'Türkçe');
	}
	if (!count($list)) {
		return $memo[$sign] = $base;	// API injoignable : les cinq langues, non conservées
	}

	// Langues favorites d'abord, puis par nom
	$favourites = array_keys($base);
	uksort($list, function ($a, $b) use ($list, $favourites) {
		$pa = array_search(strtolower(preg_replace('/[-_].*$/', '', $a)), $favourites);
		$pb = array_search(strtolower(preg_replace('/[-_].*$/', '', $b)), $favourites);
		$pa = ($pa === false ? 99 : $pa);
		$pb = ($pb === false ? 99 : $pb);
		return $pa == $pb ? strcasecmp($list[$a], $list[$b]) : $pa - $pb;
	});
	if (dol_mkdir($dir) >= 0) {
		@file_put_contents($file, json_encode($list));
	}

	return $memo[$sign] = $list;
}

/**
 * Code d'une langue pour l'API : un index (1..5) du module, ou un code du fournisseur (fr, pt-BR, EN-US...)
 *
 * @param	int|string	$value	Index ou code
 * @return	string				Code, '' si non valide
 */
function nsinfoTradIsoCode($value)
{
	$index = array(1 => 'en', 2 => 'fr', 3 => 'de', 4 => 'it', 5 => 'es');
	if (is_int($value) || (is_string($value) && ctype_digit($value))) {
		return isset($index[(int) $value]) ? $index[(int) $value] : '';
	}

	return preg_match('/^[A-Za-z]{2,3}([-_][A-Za-z0-9]{2,8})?$/', (string) $value) ? str_replace('_', '-', (string) $value) : '';
}

/**
 * Traduit un texte avec le fournisseur choisi dans les réglages
 *
 * @param	string	$text		Texte à traduire
 * @param	int		$from		Index de la langue du texte (1..5)
 * @param	int		$to			Index de la langue cible (1..5)
 * @param	bool	$ishtml		Le texte est du HTML (balises conservées par les fournisseurs qui le gèrent, supprimées sinon)
 * @param	string	$error		Message d'erreur (sortie)
 * @param	string	$provider	Fournisseur à utiliser ('' = celui des réglages)
 * @return	string|null			Texte traduit, null en cas d'erreur
 */
function nsinfoTradTranslate($text, $from, $to, $ishtml, &$error, $provider = '')
{
	$providers = nsinfoTradProviders();
	$provider = $provider !== '' ? $provider : nsinfoTradActiveProvider();
	if (!isset($providers[$provider])) {
		$error = 'NsinfoTradNotConfigured';
		return null;
	}
	// Langues : index (1..5) du module ou code de la langue du fournisseur (voir nsinfoTradLanguages())
	$src = nsinfoTradIsoCode($from);
	$dst = nsinfoTradIsoCode($to);
	if ($src === '' || $dst === '' || strtolower($src) == strtolower($dst)) {
		$error = 'NsinfoTradBadLanguages';
		return null;
	}
	if (trim($text) === '') {
		return '';
	}
	// Fournisseurs qui ne traduisent que le texte brut : le HTML est traduit texte par texte, le balisage est conservé
	if ($ishtml && empty($providers[$provider]['html'])) {
		$byNodes = nsinfoTradHtmlNodes($text, $src, $dst, $error, $provider);
		if ($byNodes !== null) {
			return $byNodes;
		}
		if ($error !== '') {
			return null;
		}
	}
	$json = array('Content-Type: application/json');

	switch ($provider) {
		case 'deepl':
			$base = (nsinfoTradSetting('deepl', 'PLAN') == 'pro') ? 'https://api.deepl.com' : 'https://api-free.deepl.com';
			// DeepL : source = langue seule (EN), cible = EN-GB / EN-US et PT-BR / PT-PT pour ces deux langues
			$deepltarget = strtoupper($dst);
			$deepltarget = ($deepltarget == 'EN' ? 'EN-US' : ($deepltarget == 'PT' ? 'PT-PT' : $deepltarget));
			$body = array('text' => array($text), 'source_lang' => strtoupper(preg_replace('/-.*$/', '', $src)), 'target_lang' => $deepltarget);
			if ($ishtml) {
				$body['tag_handling'] = 'html';
			}
			$res = nsinfoTradCall($base.'/v2/translate', 'POST', json_encode($body), array_merge($json, array('Authorization: DeepL-Auth-Key '.nsinfoTradSetting('deepl', 'KEY'))), 0);
			$data = nsinfoTradDecode($res, $error);
			return isset($data['translations'][0]['text']) ? $data['translations'][0]['text'] : nsinfoTradFail($res, $data, $error);
		case 'google':
			$body = array('q' => $text, 'source' => $src, 'target' => $dst, 'format' => ($ishtml ? 'html' : 'text'));
			$res = nsinfoTradCall('https://translation.googleapis.com/language/translate/v2?key='.urlencode(nsinfoTradSetting('google', 'KEY')), 'POST', json_encode($body), $json, 0);
			$data = nsinfoTradDecode($res, $error);
			return isset($data['data']['translations'][0]['translatedText']) ? ($ishtml ? $data['data']['translations'][0]['translatedText'] : html_entity_decode($data['data']['translations'][0]['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) : nsinfoTradFail($res, $data, $error);
		case 'azure':
			$headers = array_merge($json, array('Ocp-Apim-Subscription-Key: '.nsinfoTradSetting('azure', 'KEY')));
			if (nsinfoTradSetting('azure', 'REGION') !== '') {
				$headers[] = 'Ocp-Apim-Subscription-Region: '.nsinfoTradSetting('azure', 'REGION');
			}
			$url = rtrim(nsinfoTradSetting('azure', 'URL'), '/').'/translate?api-version=3.0&from='.$src.'&to='.$dst.'&textType='.($ishtml ? 'html' : 'plain');
			$res = nsinfoTradCall($url, 'POST', json_encode(array(array('Text' => $text))), $headers, 2);
			$data = nsinfoTradDecode($res, $error);
			return isset($data[0]['translations'][0]['text']) ? $data[0]['translations'][0]['text'] : nsinfoTradFail($res, $data, $error);
		case 'libretranslate':
			$body = array('q' => $text, 'source' => $src, 'target' => $dst, 'format' => ($ishtml ? 'html' : 'text'));
			if (nsinfoTradSetting('libretranslate', 'KEY') !== '') {
				$body['api_key'] = nsinfoTradSetting('libretranslate', 'KEY');
			}
			$res = nsinfoTradCall(rtrim(nsinfoTradSetting('libretranslate', 'URL'), '/').'/translate', 'POST', json_encode($body), $json, 2);
			$data = nsinfoTradDecode($res, $error);
			return isset($data['translatedText']) ? $data['translatedText'] : nsinfoTradFail($res, $data, $error);
		case 'mymemory':
		case 'lingva':
			// Texte brut seulement, découpé en morceaux (MyMemory : 500 octets par requête ; Lingva : le texte est dans l'URL)
			$plain = $ishtml ? trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|li|div|h[1-6])>|<br\s*\/?>/i', "\n", $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : $text;
			$out = array();
			foreach (nsinfoTradChunks($plain, $provider == 'mymemory' ? 450 : 1200) as $chunk) {
				if ($chunk === '') {
					$out[] = '';
					continue;
				}
				if ($provider == 'mymemory') {
					$url = 'https://api.mymemory.translated.net/get?q='.rawurlencode($chunk).'&langpair='.$src.'|'.$dst;
					if (nsinfoTradSetting('mymemory', 'EMAIL') !== '') {
						$url .= '&de='.rawurlencode(nsinfoTradSetting('mymemory', 'EMAIL'));
					}
					$res = nsinfoTradCall($url, 'GET', '', array(), 0);
					$data = nsinfoTradDecode($res, $error);
					if (!isset($data['responseData']['translatedText']) || (isset($data['responseStatus']) && (int) $data['responseStatus'] != 200)) {
						$msg = isset($data['responseDetails']) ? (string) $data['responseDetails'] : '';
						return nsinfoTradFail($res, array('message' => $msg), $error);
					}
					$out[] = html_entity_decode($data['responseData']['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				} else {
					$res = nsinfoTradCall(rtrim(nsinfoTradSetting('lingva', 'URL'), '/').'/api/v1/'.$src.'/'.$dst.'/'.rawurlencode($chunk), 'GET', '', array(), 2);
					$data = nsinfoTradDecode($res, $error);
					if (!isset($data['translation'])) {
						return nsinfoTradFail($res, $data, $error);
					}
					$out[] = $data['translation'];
				}
			}
			$result = implode("\n", $out);
			// Un texte HTML revient en texte brut, un paragraphe par ligne
			return $ishtml ? '<p>'.implode('</p><p>', array_filter(array_map('trim', explode("\n", $result)), 'strlen')).'</p>' : $result;
	}

	$error = 'NsinfoTradNotConfigured';
	return null;
}

/**
 * Texte HTML traduit par un fournisseur qui ne traduit que le texte brut (MyMemory, Lingva) : le balisage est conservé, seuls les textes
 * entre les balises sont traduits, un par un, puis remis en place (gras, listes, liens, couleurs... sont conservés).
 *
 * @param	string	$html		Texte HTML
 * @param	string	$from		Code de la langue du texte
 * @param	string	$to			Code de la langue cible
 * @param	string	$error		Message d'erreur (sortie)
 * @param	string	$provider	Fournisseur
 * @return	string|null			HTML traduit, null en cas d'erreur
 */
function nsinfoTradHtmlNodes($html, $from, $to, &$error, $provider)
{
	if (!class_exists('DOMDocument')) {
		return null;
	}
	$doc = new DOMDocument();
	$previous = libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="UTF-8"><div id="nsinfo-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_clear_errors();
	libxml_use_internal_errors($previous);
	$root = $doc->getElementById('nsinfo-root');
	if (!$root) {
		return null;
	}
	$xpath = new DOMXPath($doc);
	$known = array();
	foreach (iterator_to_array($xpath->query('.//text()[not(ancestor::script) and not(ancestor::style)]', $root)) as $node) {
		$value = $node->nodeValue;
		if (trim($value) === '') {
			continue;
		}
		preg_match('/^(\s*)(.*?)(\s*)$/su', $value, $m);
		$core = $m[2];
		// Un texte sans lettre (chiffres, signes) est conservé tel quel
		if (!preg_match('/\p{L}/u', $core)) {
			continue;
		}
		if (!isset($known[$core])) {
			$translated = nsinfoTradTranslate($core, $from, $to, false, $error, $provider);
			if ($translated === null) {
				return null;
			}
			$known[$core] = trim($translated) !== '' ? trim($translated) : $core;
		}
		$node->nodeValue = $m[1].$known[$core].$m[3];
	}
	$out = '';
	foreach ($root->childNodes as $child) {
		$out .= $doc->saveHTML($child);
	}

	return $out;
}

/**
 * Appel d'une API
 *
 * @param	string		$url		URL
 * @param	string		$method		GET ou POST
 * @param	string		$body		Corps (JSON) pour POST
 * @param	string[]	$headers	En-têtes
 * @param	int			$localurl	0 = URL externe seulement, 2 = aussi URL locale (instance sur le réseau)
 * @return	array<string,mixed>
 */
function nsinfoTradCall($url, $method, $body, $headers, $localurl)
{
	return getURLContent($url, ($method == 'POST' ? 'POSTALREADYFORMATED' : 'GET'), ($method == 'POST' ? $body : ''), 1, $headers, array('http', 'https'), $localurl, -1, 10, 30);
}

/**
 * Décode la réponse JSON d'une API
 *
 * @param	array<string,mixed>	$res	Résultat de getURLContent()
 * @param	string				$error	Message d'erreur (sortie)
 * @return	array<mixed>
 */
function nsinfoTradDecode($res, &$error)
{
	$data = json_decode(isset($res['content']) ? (string) $res['content'] : '', true);
	if (!empty($res['curl_error_msg'])) {
		$error = (string) $res['curl_error_msg'];
	}

	return is_array($data) ? $data : array();
}

/**
 * Erreur d'une API : message du fournisseur ou code HTTP
 *
 * @param	array<string,mixed>	$res	Résultat de getURLContent()
 * @param	array<mixed>		$data	Réponse décodée
 * @param	string				$error	Message d'erreur (sortie)
 * @return	null
 */
function nsinfoTradFail($res, $data, &$error)
{
	$msg = '';
	foreach (array('message', 'error') as $k) {
		if (isset($data[$k]) && is_scalar($data[$k])) {
			$msg = (string) $data[$k];
			break;
		}
		if (isset($data[$k]['message'])) {
			$msg = (string) $data[$k]['message'];
			break;
		}
	}
	$error = trim('HTTP '.(isset($res['http_code']) ? (int) $res['http_code'] : 0).' '.$msg.' '.(isset($res['curl_error_msg']) ? $res['curl_error_msg'] : ''));

	return null;
}

/**
 * Découpe un texte en morceaux d'au plus $max octets, sur les lignes puis sur les phrases (les lignes vides sont conservées)
 *
 * @param	string	$text	Texte
 * @param	int		$max	Taille maximale en octets d'un morceau
 * @return	string[]
 */
function nsinfoTradChunks($text, $max)
{
	$chunks = array();
	foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
		if (strlen($line) <= $max) {
			$chunks[] = $line;
			continue;
		}
		$current = '';
		foreach (preg_split('/(?<=[.!?;:])\s+/u', $line) as $sentence) {
			if ($current !== '' && strlen($current) + strlen($sentence) + 1 > $max) {
				$chunks[] = $current;
				$current = '';
			}
			while (strlen($sentence) > $max) {
				$chunks[] = mb_strcut($sentence, 0, $max, 'UTF-8');
				$sentence = substr($sentence, strlen(mb_strcut($sentence, 0, $max, 'UTF-8')));
			}
			$current = ($current === '' ? $sentence : $current.' '.$sentence);
		}
		if ($current !== '') {
			$chunks[] = $current;
		}
	}

	return $chunks;
}
