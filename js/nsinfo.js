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
 * \file    js/nsinfo.js
 * \ingroup nsinfo
 * \brief   JavaScript bootstrap for module NSINFO.
 */

if (!window.nsinfo) {
	window.nsinfo = {};
	window.nsinfo.scriptsLoaded = false;
}

if (!window.nsinfo.scriptsLoaded) {
	window.nsinfo.init = function() {
		window.nsinfo.loadListScript();
	};

	window.nsinfo.loadListScript = function() {
		if (!window.nsinfo.scriptsLoaded) {
			var key, slug;
			for (key in window.nsinfo) {
				if (window.nsinfo[key].init) {
					window.nsinfo[key].init();
				}

				for (slug in window.nsinfo[key]) {
					if (window.nsinfo[key] && window.nsinfo[key][slug] && window.nsinfo[key][slug].init) {
						window.nsinfo[key][slug].init();
					}
				}
			}

			window.nsinfo.scriptsLoaded = true;
		}
	};

	$(document).ready(window.nsinfo.init);
}
