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
 * Calendrier d'une période entre deux dates (NS INFO) : deux mois côte à côte, un clic pour le premier jour, un clic pour
 * le dernier jour, les jours de la période sont surlignés (et prévisualisés sous le curseur), les jours non travaillés
 * sont grisés, raccourcis et nombre de jours. Aucune bibliothèque.
 *
 *   nsinfoRangePicker(anchor, {start: 'YYYY-MM-DD', end: 'YYYY-MM-DD', workdays: [1,2,3,4,5], lang: 'fr-FR',
 *                               labels: {today:'', clear:'', days:'', from:'', to:''}, onChange: function(start, end) {}})
 *
 * Option single : true = un seul jour (un mois, un clic choisit le jour, début = fin), pour les champs d'une seule date.
 * Option exclusive : true = le dernier jour est le jour du retour (prêt : 4 au 6 = 2 jours, minimum 1), le nombre de jours affiché est la différence.
 */
(function (window, $) {
	'use strict';

	var current = null;

	function pad(n) { return (n < 10 ? '0' : '') + n; }
	function toIso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
	function fromIso(s) {
		var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || '');
		return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
	}
	function sameDay(a, b) { return a && b && toIso(a) === toIso(b); }
	function days(a, b) { return Math.round((b - a) / 86400000) + 1; }

	/* Dates saisies : ordre du jour, du mois et de l'année de la langue (Intl), lecture de ce qui est saisi (28/02/2026, 28.2.26, 280226,
	 * 28/02 pour l'année courante), écriture au format usuel */
	var HINTS = {fr: ['jj', 'mm', 'aaaa'], es: ['dd', 'mm', 'aaaa'], it: ['gg', 'mm', 'aaaa'], de: ['TT', 'MM', 'JJJJ']};
	function order(lang) {
		var res = [];
		try {
			$.each(new Intl.DateTimeFormat(lang).formatToParts(new Date(2030, 10, 25)), function (i, p) { if (p.type === 'day' || p.type === 'month' || p.type === 'year') { res.push(p.type); } });
		} catch (e) {}
		return res.length === 3 ? res : ['day', 'month', 'year'];
	}
	function hint(lang) {
		var h = HINTS[String(lang).substr(0, 2).toLowerCase()] || ['dd', 'mm', 'yyyy'], map = {day: h[0], month: h[1], year: h[2]};
		return $.map(order(lang), function (k) { return map[k]; }).join('/');
	}
	function fmt(d, lang) { return d ? d.toLocaleDateString(lang, {day: '2-digit', month: '2-digit', year: 'numeric'}) : ''; }
	function parseTyped(text, lang) {
		var t = $.trim(String(text)), ord = order(lang), parts;
		if (t === '') { return null; }
		if (/^\d+$/.test(t)) {	/* chiffres seuls : ddmmyyyy, ddmmyy */
			var n = t.length;
			if (n === 8) { parts = [t.substr(0, 2), t.substr(2, 2), t.substr(4, 4)]; if (ord[0] === 'year') { parts = [t.substr(0, 4), t.substr(4, 2), t.substr(6, 2)]; } }
			else if (n === 6) { parts = [t.substr(0, 2), t.substr(2, 2), t.substr(4, 2)]; }
			else if (n === 4) { parts = [t.substr(0, 2), t.substr(2, 2)]; }
			else { return false; }
		} else {
			parts = t.split(/[^0-9]+/);
			if (parts[parts.length - 1] === '') { parts.pop(); }
			if (parts.length < 2 || parts.length > 3) { return false; }
		}
		var v = {};
		if (parts.length === 2) {	/* jour et mois : l'année en cours */
			var dm = $.grep(ord, function (k) { return k !== 'year'; });
			v[dm[0]] = +parts[0]; v[dm[1]] = +parts[1]; v.year = new Date().getFullYear();
		} else {
			$.each(ord, function (i, k) { v[k] = +parts[i]; });
			/* Année sur deux chiffres : les 20 années à venir sont 20xx, les autres 19xx (10/05/75 vaut 1975, 10/05/35 vaut 2035) */
			if (v.year < 100) { v.year += 2000; if (v.year > new Date().getFullYear() + 20) { v.year -= 100; } }
		}
		var d = new Date(v.year, v.month - 1, v.day);
		return (d.getFullYear() === v.year && d.getMonth() === v.month - 1 && d.getDate() === v.day) ? d : false;
	}

	function close() {
		if (current) {
			current.el.remove();
			$(document).off('.nsinforange');
			current = null;
		}
	}

	window.nsinfoRangePicker = function (anchor, options) {
		close();
		var o = $.extend({workdays: [1, 2, 3, 4, 5], lang: 'fr-FR', labels: {}, onChange: function () {}}, options);
		var labels = $.extend({today: 'Today', clear: 'Clear', days: 'days', from: 'From', to: 'to', pick: 'Choose the first day'}, o.labels);
		var start = fromIso(o.start), end = o.single ? fromIso(o.start) : fromIso(o.end), hover = null;
		var view = start ? new Date(start.getFullYear(), start.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
		var today = new Date();
		today = new Date(today.getFullYear(), today.getMonth(), today.getDate());
		var el = $('<div class="nsinfo-cal" role="dialog"></div>').appendTo(document.body);
		current = {el: el};

		function monthTitle(d) {
			var t = d.toLocaleDateString(o.lang, {month: 'long', year: 'numeric'});
			return t.charAt(0).toUpperCase() + t.slice(1);
		}

		function month(first) {
			var html = '<div class="nsinfo-cal-month"><div class="nsinfo-cal-title">' + monthTitle(first) + '</div><div class="nsinfo-cal-grid">';
			for (var w = 0; w < 7; w++) {
				var ref = new Date(2024, 0, 1 + w);	/* un lundi + w */
				html += '<span class="nsinfo-cal-dow">' + ref.toLocaleDateString(o.lang, {weekday: 'narrow'}) + '</span>';
			}
			var offset = (first.getDay() + 6) % 7, nb = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
			for (var i = 0; i < offset; i++) { html += '<span></span>'; }
			for (var day = 1; day <= nb; day++) {
				var d = new Date(first.getFullYear(), first.getMonth(), day), iso = toIso(d), cls = 'nsinfo-cal-day', iso7 = ((d.getDay() + 6) % 7) + 1;
				var rangeEnd = end || (start && hover && hover >= start ? hover : null), rangeStart = start;
				if (!end && start && hover && hover < start) { rangeStart = hover; rangeEnd = start; }
				if (o.workdays.indexOf(iso7) < 0) { cls += ' off'; }
				if (sameDay(d, today)) { cls += ' today'; }
				if (rangeStart && sameDay(d, rangeStart)) { cls += ' edge first'; }
				if (rangeEnd && sameDay(d, rangeEnd)) { cls += ' edge last'; }
				if (rangeStart && rangeEnd && d > rangeStart && d < rangeEnd) { cls += ' inrange'; }
				html += '<button type="button" class="' + cls + '" data-iso="' + iso + '">' + day + '</button>';
			}
			return html + '</div></div>';
		}

		/* Les dates peuvent aussi être saisies : un champ pour le jour (single) ou deux champs (période) */
		function field(which, d) {
			return '<input type="text" class="nsinfo-cal-in nsinfo-cal-in-' + which + '" data-which="' + which + '" inputmode="numeric" autocomplete="off" size="10" value="' + fmt(d, o.lang) + '" placeholder="' + hint(o.lang) + '" aria-label="' + (which === 'start' ? labels.from : labels.to) + '">';
		}
		function summary() {
			if (o.single) { return field('start', start); }
			var n = (start && end) ? ' &middot; ' + (o.exclusive ? Math.max(1, days(start, end) - 1) : days(start, end)) + ' ' + labels.days : '';
			return labels.from + ' ' + field('start', start) + ' ' + labels.to + ' ' + field('end', end) + n;
		}

		function place() {
			var a = $(anchor).offset(), h = $(anchor).outerHeight(), left = Math.max(8, Math.min(a.left, $(window).width() - el.outerWidth() - 8));
			el.css({top: a.top + h + 6, left: left});
		}

		var focusKey = null;
		function render() {
			var second = new Date(view.getFullYear(), view.getMonth() + 1, 1);
			el.html('<div class="nsinfo-cal-nav"><button type="button" class="nsinfo-cal-prev" aria-label="&lsaquo;">&lsaquo;</button><span class="nsinfo-cal-summary">' + summary() + '</span><button type="button" class="nsinfo-cal-next" aria-label="&rsaquo;">&rsaquo;</button></div>'
				+ '<div class="nsinfo-cal-months">' + month(view) + (o.single ? '' : month(second)) + '</div>'
				+ '<div class="nsinfo-cal-foot"><button type="button" class="nsinfo-cal-today">' + labels.today + '</button><button type="button" class="nsinfo-cal-clear">' + labels.clear + '</button></div>');
			if (focusKey) { el.find('.nsinfo-cal-in-' + focusKey).trigger('focus').trigger('select'); focusKey = null; }
		}

		render();
		place();
		/* Clavier : le premier champ est prêt à la saisie (pas sur téléphone, où le clavier masquerait le calendrier) */
		if ($(window).width() > 640) { el.find('.nsinfo-cal-in-start').trigger('focus').trigger('select'); }

		el.on('click', '.nsinfo-cal-prev', function () { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); render(); });
		el.on('click', '.nsinfo-cal-next', function () { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); render(); });
		el.on('click', '.nsinfo-cal-today', function () { view = new Date(today.getFullYear(), today.getMonth(), 1); render(); });
		el.on('click', '.nsinfo-cal-clear', function () { start = end = hover = null; render(); o.onChange('', ''); close(); });
		/* Dates saisies : Entrée ou la sortie du champ lit la date ; Entrée sur une période complète l'applique */
		function commit(input, enter) {
			var which = $(input).data('which'), d = parseTyped($(input).val(), o.lang);
			if (d === false) { $(input).addClass('nsinfo-cal-bad'); return; }
			if (which === 'start') {
				start = d;
				if (o.single) { end = d; } else if (start && end && start > end) { end = start; }
			} else {
				end = d;
				if (start && end && end < start) { var t = start; start = end; end = t; }
			}
			if (d) { view = new Date(d.getFullYear(), d.getMonth(), 1); }
			hover = null;
			if (enter && start && (o.single || end)) { finish(start, end || start); return; }
			focusKey = (enter && which === 'start' && !o.single) ? 'end' : null;
			render();
		}
		el.on('keydown', '.nsinfo-cal-in', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); commit(this, true); } });
		el.on('change', '.nsinfo-cal-in', function () { commit(this, false); });
		/* Sélection à la souris ou au doigt : appui sur un jour puis glisser vers un autre jour (la plage est délimitée par les deux), ou clic
		 * sur le premier jour puis sur le dernier jour */
		var dragging = false, anchorDay = null;
		function finish(a, b) {
			start = a < b ? a : b;
			end = a < b ? b : a;
			hover = null;
			render();
			var s = toIso(start), e = toIso(end);
			setTimeout(function () { close(); o.onChange(s, e); }, 180);
		}
		function dayAt(x, y) {
			var node = document.elementFromPoint(x, y);
			return node && $(node).hasClass('nsinfo-cal-day') ? fromIso($(node).data('iso')) : null;
		}
		el.on('pointerdown', '.nsinfo-cal-day', function (ev) {
			ev.preventDefault();
			var d = fromIso($(this).data('iso'));
			if (o.single) {	/* un seul jour : le clic le choisit */
				finish(d, d);
				return;
			}
			if (start && !end) {	/* second clic : les jours sont les bornes */
				finish(start, d);
				return;
			}
			start = d; end = null; hover = d; anchorDay = d; dragging = true;
			render();
		});
		el.on('mouseover', '.nsinfo-cal-day', function () {
			if (start && !end) {
				var d = fromIso($(this).data('iso'));
				if (!sameDay(d, hover)) { hover = d; render(); }
			}
		});
		el.on('pointermove', function (ev) {
			if (!dragging || ev.pointerType === 'mouse') { return; }	/* le tactile n'a pas de survol : suivre le doigt */
			var d = dayAt(ev.clientX, ev.clientY);
			if (d && !sameDay(d, hover)) { hover = d; render(); }
		});
		$(document).on('pointerup.nsinforange', function () {
			if (!dragging) { return; }
			dragging = false;
			if (hover && anchorDay && !sameDay(hover, anchorDay)) {
				finish(anchorDay, hover);
			} else if (start && !end) {
				hover = null;
				render();	/* clic simple : le premier jour est choisi, le dernier est le clic suivant */
			}
		});
		$(document).on('mousedown.nsinforange', function (ev) {
			if (!$(ev.target).closest('.nsinfo-cal').length && !$(ev.target).closest(anchor).length) { close(); }
		}).on('keydown.nsinforange', function (ev) {
			if (ev.key === 'Escape') { close(); }
		});
	};
})(window, jQuery);
