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
 * \file    nsinfo/lib/htmlimport.lib.php
 * \ingroup nsinfo
 * \brief   Import of HTML code by drag and drop (or button) into a field, shared by the modules NS INFO
 */


/**
 * Zone to insert HTML into a field by drag and drop (or with a button) : the file (.html, .htm, .txt) or the text dropped
 * replaces the content of the field. In the editor (CKEditor) the code is inserted as text, like the structures already
 * written by hand (tags escaped, see producttoolsStructureToHtml()) ; in a simple textarea it is inserted as it is.
 *
 * @param	string	$field	Name of the field (e.g. codehtml)
 * @return	string			HTML and script
 */
function nsinfoHtmlDropZone($field)
{
	global $langs;

	$langs->load('nsinfo@nsinfo');
	$id = 'nsinfo-drop-'.preg_replace('/[^a-z0-9_]/i', '', $field);
	$out = '<style>
#'.$id.' { border: 2px dashed rgba(128,128,128,.5); border-radius: 8px; padding: 14px; margin-bottom: 14px; text-align: center; transition: background .15s, border-color .15s; }
#'.$id.'.over { background: rgba(34,167,216,.12); border-color: #22A7D8; }
</style>';
	$out .= '<div id="'.$id.'"><span class="fas fa-file-code pictofixedwidth" style="color: #3879B2; font-size: 1.4em"></span>';
	$out .= '<b>'.$langs->trans('NsinfoDropTitle').'</b><br><span class="opacitymedium small">'.$langs->trans('NsinfoDropHelp').'</span><br>';
	$out .= '<button type="button" class="button margintoponly" id="'.$id.'-btn"><span class="fas fa-file-import pictofixedwidth"></span>'.$langs->trans('NsinfoDropButton').'</button>';
	$out .= '<input type="file" id="'.$id.'-file" accept=".html,.htm,.txt,text/html,text/plain" style="display:none"></div>';
	$out .= '<script>
(function () {
	var zone = document.getElementById('.json_encode($id).'), file = document.getElementById('.json_encode($id.'-file').'), name = '.json_encode($field).';
	var confirmText = '.json_encode($langs->transnoentitiesnoconv('NsinfoDropConfirm')).', doneText = '.json_encode($langs->transnoentitiesnoconv('NsinfoDropDone')).';
	function current() {
		if (window.CKEDITOR && CKEDITOR.instances[name]) { return CKEDITOR.instances[name].getData().replace(/<[^>]*>/g, "").trim(); }
		var t = document.querySelector("[name=" + name + "]"); return t ? t.value.trim() : "";
	}
	function insert(text) {
		if (text.trim() === "") { return; }
		if (current() !== "" && !window.confirm(confirmText)) { return; }
		if (window.CKEDITOR && CKEDITOR.instances[name]) {
			var esc = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/^( +)/gm, function (m) { return new Array(m.length + 1).join("&nbsp;"); });
			CKEDITOR.instances[name].setData("<p>" + esc.replace(/\r?\n/g, "<br>") + "</p>");
		} else {
			var t = document.querySelector("[name=" + name + "]"); if (t) { t.value = text; t.dispatchEvent(new Event("change", {bubbles: true})); }
		}
		if (window.$ && $.jnotify) { $.jnotify(doneText, "ok"); }
	}
	function readFile(f) { if (!f) { return; } var r = new FileReader(); r.onload = function () { insert(String(r.result)); }; r.readAsText(f); }
	document.getElementById('.json_encode($id.'-btn').').addEventListener("click", function () { file.click(); });
	file.addEventListener("change", function () { readFile(file.files[0]); file.value = ""; });
	["dragenter", "dragover"].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add("over"); }); });
	["dragleave", "drop"].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove("over"); }); });
	zone.addEventListener("drop", function (e) {
		var dt = e.dataTransfer;
		if (dt.files && dt.files.length) { readFile(dt.files[0]); return; }
		insert(dt.getData("text/plain") || dt.getData("text/html") || "");
	});
})();
</script>';

	return $out;
}


/**
 * Convert a file of HTML code into the way the code of a structure is stored (the editor keeps the tags as text :
 * escaped, one line per <br>, indentation in non-breaking spaces ; producttoolsStructureToHtml() gives the HTML back)
 *
 * @param	string	$text	Content of the file (UTF-8)
 * @return	string
 */
function nsinfoHtmlToStored($text)
{
	$text = str_replace(array("\r\n", "\r"), "\n", $text);
	$lines = array();
	foreach (explode("\n", $text) as $line) {
		$escaped = htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8');
		$lines[] = preg_replace_callback('/^ +/', function ($m) {
			return str_repeat('&nbsp;', strlen($m[0]));
		}, $escaped);
	}

	return '<p>'.implode("<br>\n", $lines).'</p>';
}


/**
 * Button and window (drop zone) to import a file of HTML code into a structure : the code of the structure is replaced
 *
 * @param	string					$url		Url of the page
 * @param	array<string,string>	$hiddens	Hidden values (id)
 * @param	int						$disabled	1 = button inactive (no right)
 * @param	string					$action		Action posted to the page
 * @param	string					$filename	Name of the file field ($_FILES[$filename]['tmp_name'][0])
 * @return	string							HTML
 */
function nsinfoHtmlImportDialog($url, $hiddens = array(), $disabled = 0, $action = 'importhtml', $filename = 'nsinfo_html')
{
	global $langs;

	dol_include_once('/nsinfo/lib/nsinfo.lib.php');
	$langs->load('nsinfo@nsinfo');
	$title = $langs->trans('NsinfoHtmlImport');
	$out = '<a class="butAction'.($disabled ? ' butActionRefused classfortooltip' : '').'" id="nsinfo-html-btn" href="#"><span class="fa fa-file-import paddingright"></span>'.dol_escape_htmltag($title).'</a>';
	$out .= '<div id="nsinfo-html-dialog" class="hideobject" title="'.dol_escape_htmltag($title).'">';
	$out .= '<div class="opacitymedium marginbottomonly">'.$langs->trans('NsinfoHtmlImportHelp').'</div>';
	if (function_exists('nsinfoDropZone')) {
		$out .= nsinfoDropZone($filename, $url, $action, $hiddens, $langs->trans('NsinfoHtmlImportDrop'), '.html,.htm,.txt,text/html,text/plain', $disabled);
	}
	$out .= '</div>';
	$out .= '<script'.(function_exists('getNonce') ? ' nonce="'.getNonce().'"' : '').'>
jQuery(function($) {
	$("#nsinfo-html-btn").on("click", function(e) {
		e.preventDefault();'.($disabled ? ' return;' : '').'
		$("#nsinfo-html-dialog").removeClass("hideobject").dialog({modal: true, width: Math.min(560, $(window).width() - 20), resizable: false});
	});
});
</script>';

	return $out;
}
