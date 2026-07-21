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
 * \file    js/modules/toolbox.js
 * \ingroup nsinfo
 * \brief   JavaScript toolbox helpers for module NSINFO.
 */

window.nsinfo.toolbox = {};

window.nsinfo.toolbox.init = function() {
	window.nsinfo.toolbox.event();
};

window.nsinfo.toolbox.event = function() {
};

/**
 * Return suitable query separator
 *
 * @param  {string} url Url of current page
 * @return {string}     Suitable query separator
 */
window.nsinfo.toolbox.getQuerySeparator = function(url) {
	return url.match(/\?/) ? '&' : '?';
};

/**
 * Return security token value
 *
 * @return {string} Security token value
 */
window.nsinfo.toolbox.getToken = function() {
	return $('input[name="token"]').val();
};
