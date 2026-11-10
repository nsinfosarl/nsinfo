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
 * Calendar of a period between two dates (NS INFO) : two months side by side, one click for the first day, one click for
 * the last day, the days of the period are highlighted (and previewed under the pointer), the days that are not worked
 * are greyed, shortcuts and number of days. No library.
 *
 *   nsinfoRangePicker(anchor, {start: 'YYYY-MM-DD', end: 'YYYY-MM-DD', workdays: [1,2,3,4,5], lang: 'fr-FR',
 *                               labels: {today:'', clear:'', days:'', from:'', to:''}, onChange: function(start, end) {}})
 *
 * Option single: true = one day only (one month, one click chooses the day, start = end), for the fields of one date.
 * Option exclusive: true = the last day is the day of the return (loan : 4 to 6 = 2 days, minimum 1), the number of days shown is the difference.
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

	/* Typed dates : order of the day, month and year of the language (Intl), reading of what is typed (28/02/2026, 28.2.26, 280226,
	 * 28/02 for the current year), writing in the usual form */
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
		if (/^\d+$/.test(t)) {	/* digits only : ddmmyyyy, ddmmyy */
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
		if (parts.length === 2) {	/* day and month : the current year */
			var dm = $.grep(ord, function (k) { return k !== 'year'; });
			v[dm[0]] = +parts[0]; v[dm[1]] = +parts[1]; v.year = new Date().getFullYear();
		} else {
			$.each(ord, function (i, k) { v[k] = +parts[i]; });
			/* Year on two digits : the 20 coming years are 20xx, the others 19xx (10/05/75 is 1975, 10/05/35 is 2035) */
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
				var ref = new Date(2024, 0, 1 + w);	/* a Monday + w */
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

		/* The dates can also be typed : one field for the day (single) or two fields (period) */
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
		/* Keyboard : the first field is ready to type (not on a phone, where the keyboard would hide the calendar) */
		if ($(window).width() > 640) { el.find('.nsinfo-cal-in-start').trigger('focus').trigger('select'); }

		el.on('click', '.nsinfo-cal-prev', function () { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); render(); });
		el.on('click', '.nsinfo-cal-next', function () { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); render(); });
		el.on('click', '.nsinfo-cal-today', function () { view = new Date(today.getFullYear(), today.getMonth(), 1); render(); });
		el.on('click', '.nsinfo-cal-clear', function () { start = end = hover = null; render(); o.onChange('', ''); close(); });
		/* Typed dates : Enter or leaving the field reads the date ; Enter on a complete period applies it */
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
		/* Selection with the mouse or the finger : press on a day and drag to another day (the range is the bounds), or click
		 * the first day then the last day */
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
			if (o.single) {	/* one day : the click chooses it */
				finish(d, d);
				return;
			}
			if (start && !end) {	/* second click : the days are the bounds */
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
			if (!dragging || ev.pointerType === 'mouse') { return; }	/* the touch has no hover : follow the finger */
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
				render();	/* simple click : the first day is chosen, the last one is the next click */
			}
		});
		$(document).on('mousedown.nsinforange', function (ev) {
			if (!$(ev.target).closest('.nsinfo-cal').length && !$(ev.target).closest(anchor).length) { close(); }
		}).on('keydown.nsinforange', function (ev) {
			if (ev.key === 'Escape') { close(); }
		});
	};
})(window, jQuery);
