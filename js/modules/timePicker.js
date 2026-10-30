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
 * Clock to choose a time (NS INFO), like the one of Android : the hour on a dial (outer ring 1 to 12, inner ring 13 to 00),
 * then the minutes on the dial. Mouse or finger (press, drag, release). No library.
 *
 *   nsinfoTimePicker(anchor, {value: 'HH:MM', lang: 'fr-FR', step: 1, labels: {ok:'', cancel:'', now:'', clear:''},
 *                             onChange: function(hhmm) {}})      hhmm = 'HH:MM', '' when cleared
 */
(function (window, $) {
	'use strict';

	var current = null, SIZE = 232, C = SIZE / 2, R_OUT = 92, R_IN = 60;

	function pad(n) { return (n < 10 ? '0' : '') + n; }

	function close() {
		if (current) {
			current.el.remove();
			$(document).off('.nsinfoclock');
			current = null;
		}
	}

	window.nsinfoTimePicker = function (anchor, options) {
		close();
		var o = $.extend({lang: 'fr-FR', step: 1, labels: {}, onChange: function () {}}, options);
		var labels = $.extend({ok: 'OK', cancel: 'Cancel', now: 'Now', clear: 'Clear'}, o.labels);
		var step = Math.max(1, +o.step || 1);
		var m = /^(\d{1,2}):(\d{2})/.exec(o.value || ''), now = new Date();
		var h = m ? Math.min(23, +m[1]) : now.getHours(), min = m ? Math.min(59, +m[2]) : Math.round(now.getMinutes() / step) * step % 60;
		var mode = 'hour', dragging = false;
		var el = $('<div class="nsinfo-clock" role="dialog"></div>').appendTo(document.body);
		current = {el: el};

		function xy(pos, r) {
			var a = pos / 12 * 2 * Math.PI;
			return {x: C + r * Math.sin(a), y: C - r * Math.cos(a)};
		}

		function dial() {
			var html = '', handpos, handr, label = '', sel = false, i, p, v;
			if (mode === 'hour') {
				handpos = (h % 12); handr = (h >= 1 && h <= 12) ? R_OUT : R_IN;
				label = pad(h);
				for (i = 1; i <= 12; i++) {
					p = xy(i % 12, R_OUT);
					html += '<span class="nsinfo-clock-n' + (h === i ? ' sel' : '') + '" style="left:' + p.x + 'px;top:' + p.y + 'px">' + i + '</span>';
				}
				for (i = 0; i < 12; i++) {
					v = i === 0 ? 0 : 12 + i;
					p = xy(i, R_IN);
					html += '<span class="nsinfo-clock-n in' + (h === v ? ' sel' : '') + '" style="left:' + p.x + 'px;top:' + p.y + 'px">' + pad(v) + '</span>';
				}
			} else {
				handpos = min / 5; handr = R_OUT;
				label = (min % 5 === 0) ? '' : pad(min);
				for (i = 0; i < 12; i++) {
					p = xy(i, R_OUT);
					html += '<span class="nsinfo-clock-n' + (min === i * 5 ? ' sel' : '') + '" style="left:' + p.x + 'px;top:' + p.y + 'px">' + pad(i * 5) + '</span>';
				}
			}
			var end = xy(handpos, handr);
			var svg = '<svg class="nsinfo-clock-hand" width="' + SIZE + '" height="' + SIZE + '"><line x1="' + C + '" y1="' + C + '" x2="' + end.x + '" y2="' + end.y + '"/><circle cx="' + C + '" cy="' + C + '" r="3"/><circle class="tip" cx="' + end.x + '" cy="' + end.y + '" r="17"/>'
				+ (label ? '<text x="' + end.x + '" y="' + end.y + '" dy=".35em">' + label + '</text>' : '') + '</svg>';
			return '<div class="nsinfo-clock-dial" style="width:' + SIZE + 'px;height:' + SIZE + 'px">' + svg + html + '</div>';
		}

		function render() {
			el.html('<div class="nsinfo-clock-head"><button type="button" class="nsinfo-clock-seg h' + (mode === 'hour' ? ' on' : '') + '">' + pad(h) + '</button><span>:</span><button type="button" class="nsinfo-clock-seg m' + (mode === 'min' ? ' on' : '') + '">' + pad(min) + '</button></div>'
				+ dial()
				+ '<div class="nsinfo-clock-foot"><button type="button" class="nsinfo-clock-now">' + labels.now + '</button><button type="button" class="nsinfo-clock-clear">' + labels.clear + '</button><span class="nsinfo-clock-sp"></span><button type="button" class="nsinfo-clock-cancel">' + labels.cancel + '</button><button type="button" class="nsinfo-clock-ok">' + labels.ok + '</button></div>');
		}

		function place() {
			var a = $(anchor).offset(), hh = $(anchor).outerHeight(), left = Math.max(8, Math.min(a.left, $(window).width() - el.outerWidth() - 8));
			el.css({top: a.top + hh + 6, left: left});
		}

		function commit() {
			var v = pad(h) + ':' + pad(min);
			close();
			o.onChange(v);
		}

		function fromPoint(x, y) {
			var r = el.find('.nsinfo-clock-dial')[0].getBoundingClientRect(), dx = x - (r.left + r.width / 2), dy = y - (r.top + r.height / 2);
			var deg = (Math.atan2(dx, -dy) * 180 / Math.PI + 360) % 360, dist = Math.sqrt(dx * dx + dy * dy) * (SIZE / r.width);
			if (mode === 'hour') {
				var pos = Math.round(deg / 30) % 12;
				h = dist < (R_OUT + R_IN) / 2 ? (pos === 0 ? 0 : 12 + pos) : (pos === 0 ? 12 : pos);
			} else {
				min = (Math.round(deg / 6 / step) * step) % 60;
			}
			render();
		}

		render();
		place();

		el.on('pointerdown', '.nsinfo-clock-dial', function (ev) { ev.preventDefault(); dragging = true; fromPoint(ev.clientX, ev.clientY); });
		el.on('click', '.nsinfo-clock-seg.h', function () { mode = 'hour'; render(); });
		el.on('click', '.nsinfo-clock-seg.m', function () { mode = 'min'; render(); });
		el.on('click', '.nsinfo-clock-ok', commit);
		el.on('click', '.nsinfo-clock-cancel', close);
		el.on('click', '.nsinfo-clock-now', function () { var d = new Date(); h = d.getHours(); min = Math.round(d.getMinutes() / step) * step % 60; commit(); });
		el.on('click', '.nsinfo-clock-clear', function () { close(); o.onChange(''); });
		$(document).on('pointermove.nsinfoclock', function (ev) { if (dragging) { fromPoint(ev.clientX, ev.clientY); } });
		$(document).on('pointerup.nsinfoclock', function () {
			if (!dragging) { return; }
			dragging = false;
			if (mode === 'hour') { mode = 'min'; render(); } else { commit(); }
		});
		$(document).on('mousedown.nsinfoclock', function (ev) {
			if (!$(ev.target).closest('.nsinfo-clock').length && !$(ev.target).closest(anchor).length) { close(); }
		}).on('keydown.nsinfoclock', function (ev) {
			if (ev.key === 'Escape') { close(); }
		});
	};
})(window, jQuery);
