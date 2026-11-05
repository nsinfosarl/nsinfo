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
 * \brief   Automatic translation through an external API (DeepL, Google, Azure, LibreTranslate, MyMemory, Lingva), shared by the modules NS INFO (setup page nsinfo/admin/trad_module.php?namemodule=<module>)
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

/**
 * Module whose settings of translation are used (the module that calls the functions of this library). The settings are either
 * common to all the modules (default) or specific to the module (option of the setup page, constant NSINFO_TRAD_SCOPE_<MODULE>).
 * To call once at the beginning of the page / of the ajax script of a module, before the other functions.
 *
 * @param	string|null	$module		Code of the module (null = only read the current one)
 * @return	string					Code of the current module ('' = none, common settings)
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
 * Is the setup of the module specific (not common to all the modules) ?
 *
 * @param	string	$module		Code of the module ('' = the current one)
 * @return	bool
 */
function nsinfoTradIsModuleScope($module = '')
{
	$module = $module !== '' ? preg_replace('/[^a-z0-9_]/', '', strtolower($module)) : nsinfoTradContext();

	return ($module !== '' && $module !== 'nsinfo' && getDolGlobalString('NSINFO_TRAD_SCOPE_'.strtoupper($module)) === 'module');
}

/**
 * Prefix of the constants of the settings : NSINFO_TRAD_ (common to all the modules) or NSINFO_TRAD_<MODULE>_ (specific to the module)
 *
 * @param	string	$module		Code of the module ('' = the current one)
 * @return	string
 */
function nsinfoTradPrefix($module = '')
{
	$module = $module !== '' ? preg_replace('/[^a-z0-9_]/', '', strtolower($module)) : nsinfoTradContext();

	return nsinfoTradIsModuleScope($module) ? 'NSINFO_TRAD_'.strtoupper($module).'_' : 'NSINFO_TRAD_';
}

/**
 * Providers of automatic translation, with the settings each one needs and its conditions of use
 *
 * Settings : name (suffix of the constant NSINFO_TRAD_<PROVIDER>_<NAME>), label (language key), type (text, password,
 * select), required, default, help (language key), options (for select).
 * The conditions (free quota, limits, what is needed) are language keys NsinfoTrad<Provider>Cond, written from the
 * documentation of each provider.
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
 * Links of a provider (create the account / get the key, documentation), always opened in a new tab
 *
 * @param	array<string,mixed>	$provider	Definition of the provider (see nsinfoTradProviders())
 * @param	Translate			$langs		Translate
 * @return	string				HTML
 */
function nsinfoTradLinksHtml($provider, $langs)
{
	$links = isset($provider['links']) ? $provider['links'] : array();
	$links[] = array('NsinfoTradDoc', $provider['doc']);
	$out = '<div class="margintoponly">';
	foreach ($links as $link) {
		$out .= '<div><a href="'.dol_escape_htmltag($link[1]).'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'fa-external-link-alt', 'class="pictofixedwidth"').$langs->transnoentitiesnoconv($link[0]).'</a></div>';
	}

	return $out.'</div>';
}

/**
 * Position of the translation picto set in the setup : right (beside the field), top or bottom (inside the field, on the right)
 *
 * @return	string	'right', 'top' or 'bottom'
 */
function nsinfoTradPosition()
{
	$pos = getDolGlobalString(nsinfoTradPrefix().'POSITION');

	return in_array($pos, array('right', 'top', 'bottom')) ? $pos : 'right';
}

/**
 * Fields of the module that have the translation picto : codes ticked in the setup (constant <prefix>FIELDS, comma separated).
 * The module lists its translatable fields with a function <module>TradFields() returning code => label ; none is ticked
 * by default : the picto is shown only on the fields asked for.
 *
 * @return	string[]
 */
function nsinfoTradFields()
{
	return array_values(array_filter(array_map('trim', explode(',', getDolGlobalString(nsinfoTradPrefix().'FIELDS')))));
}

/**
 * Has this field the translation picto ? (an API is set up and the field is ticked in the setup)
 *
 * @param	string	$code	Code of the field, as given by <module>TradFields()
 * @return	bool
 */
function nsinfoTradFieldEnabled($code)
{
	return nsinfoTradActiveProvider() !== '' && in_array($code, nsinfoTradFields(), true);
}

/**
 * Color of the translation picto set in the setup (green by default)
 *
 * @return	string	Color #rrggbb
 */
function nsinfoTradColor()
{
	$color = getDolGlobalString(nsinfoTradPrefix().'COLOR');

	return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#2E7D32';
}

/**
 * Style of the translation picto (to print once in the page)
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
 * Opening of the wrapper of a field that has a translation picto
 *
 * @return	string	HTML
 */
function nsinfoTradWrapOpen()
{
	return '<span class="nsinfo-trad-wrap nsinfo-trad-'.nsinfoTradPosition().'">';
}

/**
 * Picto of translation of a field (nothing else : no text), to print after the field inside nsinfoTradWrapOpen() ... </span>
 *
 * @param	array<string,string|int>	$data	Data attributes (data-xxx) read by the script of the page
 * @param	string						$title	Title (tooltip)
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
 * Value of a setting of a provider (constant NSINFO_TRAD_<PROVIDER>_<NAME>), or its default value
 *
 * @param	string	$provider	Code of the provider
 * @param	string	$name		Name of the setting
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
 * Provider chosen in the setup, only if its required settings are filled in
 *
 * @return	string	Code of the provider, '' if none or incomplete
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
 * Index (1 = en_US, 2 = fr_FR, 3 = de_DE, 4 = it_IT, 5 = es_ES) of a locale, 0 if not one of the 5 languages of the module
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
 * Translate a text with the provider chosen in the setup
 *
 * @param	string	$text		Text to translate
 * @param	int		$from		Index of the language of the text (1..5)
 * @param	int		$to			Index of the target language (1..5)
 * @param	bool	$ishtml		The text is HTML (tags kept by the providers that support it, removed otherwise)
 * @param	string	$error		Error message (output)
 * @param	string	$provider	Provider to use ('' = the one of the setup)
 * @return	string|null			Translated text, null on error
 */
function nsinfoTradTranslate($text, $from, $to, $ishtml, &$error, $provider = '')
{
	$providers = nsinfoTradProviders();
	$provider = $provider !== '' ? $provider : nsinfoTradActiveProvider();
	if (!isset($providers[$provider])) {
		$error = 'NsinfoTradNotConfigured';
		return null;
	}
	$iso = array(1 => 'en', 2 => 'fr', 3 => 'de', 4 => 'it', 5 => 'es');
	if (empty($iso[$from]) || empty($iso[$to]) || $from == $to) {
		$error = 'NsinfoTradBadLanguages';
		return null;
	}
	if (trim($text) === '') {
		return '';
	}
	$src = $iso[$from];
	$dst = $iso[$to];
	$json = array('Content-Type: application/json');

	switch ($provider) {
		case 'deepl':
			$base = (nsinfoTradSetting('deepl', 'PLAN') == 'pro') ? 'https://api.deepl.com' : 'https://api-free.deepl.com';
			$body = array('text' => array($text), 'source_lang' => strtoupper($src), 'target_lang' => ($dst == 'en' ? 'EN-US' : strtoupper($dst)));
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
			// Plain text only, cut into pieces (MyMemory : 500 bytes by request ; Lingva : the text is in the URL)
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
			// A text that was HTML comes back as plain text, one paragraph per line
			return $ishtml ? '<p>'.implode('</p><p>', array_filter(array_map('trim', explode("\n", $result)), 'strlen')).'</p>' : $result;
	}

	$error = 'NsinfoTradNotConfigured';
	return null;
}

/**
 * Call of an API
 *
 * @param	string		$url		URL
 * @param	string		$method		GET or POST
 * @param	string		$body		Body (JSON) for POST
 * @param	string[]	$headers	Headers
 * @param	int			$localurl	0 = external URL only, 2 = also local URL (instance on the network)
 * @return	array<string,mixed>
 */
function nsinfoTradCall($url, $method, $body, $headers, $localurl)
{
	return getURLContent($url, ($method == 'POST' ? 'POSTALREADYFORMATED' : 'GET'), ($method == 'POST' ? $body : ''), 1, $headers, array('http', 'https'), $localurl, -1, 10, 30);
}

/**
 * Decode the JSON answer of an API
 *
 * @param	array<string,mixed>	$res	Result of getURLContent()
 * @param	string				$error	Error message (output)
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
 * Error of an API : message of the provider or HTTP code
 *
 * @param	array<string,mixed>	$res	Result of getURLContent()
 * @param	array<mixed>		$data	Answer decoded
 * @param	string				$error	Error message (output)
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
 * Cut a text into pieces of at most $max bytes, on the lines then on the sentences (the empty lines are kept)
 *
 * @param	string	$text	Text
 * @param	int		$max	Maximum size in bytes of a piece
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
