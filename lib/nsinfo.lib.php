<?php
/* Copyright (C) 2022-2024 Nicolas SILOBRE <contact@ns-info.fr>
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
 * \file    nsinfo/lib/nsinfo.lib.php
 * \ingroup nsinfo
 * \brief   Library files with common functions for Nsinfo
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function nsinfoAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("nsinfo@nsinfo");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/nsinfo/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cogs paddingright pictofixedwidth valignmiddle"></i>' . $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/nsinfo/admin/changelog.php", 1);
	$head[$h][1] = '<i class="fas fa-code-branch paddingright pictofixedwidth valignmiddle"></i>' . $langs->trans("NSINFOChangeLog");
	$head[$h][2] = 'changelog';
	$h++;
	$head[$h][0] = dol_buildpath("/nsinfo/admin/about.php", 1);
	$head[$h][1] = '<i class="fab fa-readme paddingright pictofixedwidth valignmiddle"></i>' . $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'nsinfo');

	return $head;
}


