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

		function summary() {
			if (o.single) { return start ? '<b>' + start.toLocaleDateString(o.lang) + '</b>' : labels.pick; }
			if (start && end) { return labels.from + ' <b>' + start.toLocaleDateString(o.lang) + '</b> ' + labels.to + ' <b>' + end.toLocaleDateString(o.lang) + '</b> &middot; ' + days(start, end) + ' ' + labels.days; }
			if (start) { return labels.from + ' <b>' + start.toLocaleDateString(o.lang) + '</b> &hellip;'; }
			return labels.pick;
		}

		function render() {
			var second = new Date(view.getFullYear(), view.getMonth() + 1, 1);
			el.html('<div class="nsinfo-cal-nav"><button type="button" class="nsinfo-cal-prev" aria-label="&lsaquo;">&lsaquo;</button><span class="nsinfo-cal-summary">' + summary() + '</span><button type="button" class="nsinfo-cal-next" aria-label="&rsaquo;">&rsaquo;</button></div>'
				+ '<div class="nsinfo-cal-months">' + month(view) + (o.single ? '' : month(second)) + '</div>'
				+ '<div class="nsinfo-cal-foot"><button type="button" class="nsinfo-cal-today">' + labels.today + '</button><button type="button" class="nsinfo-cal-clear">' + labels.clear + '</button></div>');
		}

		function place() {
			var a = $(anchor).offset(), h = $(anchor).outerHeight(), left = Math.max(8, Math.min(a.left, $(window).width() - el.outerWidth() - 8));
			el.css({top: a.top + h + 6, left: left});
		}

		render();
		place();

		el.on('click', '.nsinfo-cal-prev', function () { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); render(); });
		el.on('click', '.nsinfo-cal-next', function () { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); render(); });
		el.on('click', '.nsinfo-cal-today', function () { view = new Date(today.getFullYear(), today.getMonth(), 1); render(); });
		el.on('click', '.nsinfo-cal-clear', function () { start = end = hover = null; render(); o.onChange('', ''); close(); });
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
