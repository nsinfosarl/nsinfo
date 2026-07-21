/* Copyright (C) 2026 NSINFO <contact@ns-info.fr>
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
 * \file    js/modules/loader.js
 * \ingroup nsinfo
 * \brief   JavaScript loader indicator for module NSINFO.
 */

window.nsinfo.loader = {};

window.nsinfo.loader.init = function() {
	window.nsinfo.loader.event();
};

window.nsinfo.loader.event = function() {
};

/**
 * Shows loader on selected element
 *
 * @param  {jQuery} element Element to show the loader on
 * @return {void}
 */
window.nsinfo.loader.display = function(element) {
	if (!element || !element.length) {
		return;
	}
	element.addClass('wpeo-loader');
	var el = $('<span class="loader-spin"></span>');
	element[0].loaderElement = el;
	element.append(element[0].loaderElement);
};

/**
 * Removes loader on selected element
 *
 * @param  {jQuery} element Element to remove the loader from
 * @return {void}
 */
window.nsinfo.loader.remove = function(element) {
	if (element && element.length) {
		element.removeClass('wpeo-loader');
		$(element[0].loaderElement).remove();
	}
};
