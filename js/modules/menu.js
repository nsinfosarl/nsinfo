/* Copyright (C) 2026 Nicolas SILOBRE <contact@ns-info.fr>
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
 * \file    js/modules/menu.js
 * \ingroup nsinfo
 * \brief   Réduction du menu de gauche à ses icônes (même technique que le module Saturne), et groupes de menus
 *          déroulants (nsinfoMenuGroupParent / nsinfoMenuGroupChild, voir setGroups).
 *          L'entrée de menu est fournie par nsinfoMenuMinimize() (lib/nsinfo.lib.php) : son icône porte la classe
 *          « nsinfo-toggle-menu » et les libellés dans data-title-minimize / data-title-maximize.
 *          Le choix est mémorisé dans le navigateur (localStorage « nsinfo_menu_minimized »).
 */

window.nsinfo.menu = {};

/**
 * Initialisation
 *
 * @return {void}
 */
window.nsinfo.menu.init = function() {
	window.nsinfo.menu.setGroups();
	window.nsinfo.menu.setMenu();
};

/**
 * Groupes de menus déroulants : les sous-menus de l'entrée marquée par nsinfoMenuGroupParent('groupe')
 * (lib/nsinfo.lib.php) et les entrées marquées par nsinfoMenuGroupChild('groupe'), avec leurs sous-menus, sont rangés
 * sous cette entrée ; une flèche les déplie ou les replie. Choix mémorisé dans le navigateur (localStorage « nsinfo_menugroup_<groupe> »),
 * ouvert par défaut, et toujours ouvert sur une page de l'une des entrées du groupe.
 *
 * @return {void}
 */
window.nsinfo.menu.setGroups = function() {
	const menuLeft = $('#id-left div.vmenu').first();
	const blockSelector = '.blockvmenu, .blockvmenulast, .blockvmenufirst, .blockvmenupair, .blockvmenuimpair';

	menuLeft.find('.nsinfo-mgp').each(function() {
		const group       = $(this).attr('data-mg');
		const parentBlock = $(this).closest(blockSelector);
		if (!group || parentBlock.length === 0 || parentBlock.find('.nsinfo-menugroup-sub').length) {
			return;
		}

		// Sous-menus propres de l'entrée parente, puis blocs des entrées du groupe dans l'ordre du menu,
		// rangés dans un conteneur sous l'entrée parente
		const sub = $('<div class="nsinfo-menugroup-sub"></div>');
		parentBlock.children('.menu_contenu').each(function() {
			sub.append($(this));
		});
		menuLeft.find('.nsinfo-mgc').filter(function() {
			return $(this).attr('data-mg') === group;
		}).each(function() {
			const childBlock = $(this).closest(blockSelector);
			if (childBlock.length && !childBlock.is(parentBlock)) {
				sub.append(childBlock.addClass('nsinfo-menugroup-child-block'));
			}
		});
		if (sub.children().length === 0) {
			return;
		}
		// Juste sous le bloc parent (option « outside »), sinon dans ce bloc avant son séparateur de fin s'il existe
		const menuEnd = parentBlock.children('.menu_end').first();
		if ($(this).attr('data-mgout') === '1') {
			sub.addClass('nsinfo-menugroup-outside').insertAfter(parentBlock);
		} else if (menuEnd.length) {
			sub.insertBefore(menuEnd);
		} else {
			parentBlock.append(sub);
		}

		// État : ouvert sur une page du groupe, sinon dernier choix (ouvert par défaut)
		const storageKey = 'nsinfo_menugroup_' + group;
		let open = true;
		try {
			open = (window.localStorage.getItem(storageKey) !== '0');
		} catch (e) {
			// stockage indisponible : ouvert
		}
		sub.find('a[href]').each(function() {
			if (this.pathname === window.location.pathname) {
				open = true;
			}
		});

		const toggle = $('<span class="nsinfo-menugroup-toggle fas" role="button" tabindex="0"></span>');
		parentBlock.find('.menu_titre').first().append(toggle);

		const apply = function() {
			sub.toggle(open);
			toggle.toggleClass('fa-chevron-down', open).toggleClass('fa-chevron-right', !open).attr('aria-expanded', open ? 'true' : 'false');
		};
		const switchState = function(event) {
			event.preventDefault();
			event.stopPropagation();
			open = !open;
			apply();
			try {
				window.localStorage.setItem(storageKey, open ? '1' : '0');
			} catch (e) {
				// stockage indisponible : choix non mémorisé
			}
		};
		toggle.on('click', switchState).on('keydown', function(event) {
			if (event.key === 'Enter' || event.key === ' ') {
				switchState(event);
			}
		});
		apply();
	});
};

/**
 * Réduction / agrandissement du menu de gauche
 *
 * @return {void}
 */
window.nsinfo.menu.setMenu = function() {
	const menuLeft        = $('#id-left div.vmenu').first();	// Conteneur du menu (et non les entrées a.vmenu / span.vmenu)
	const menuElement     = menuLeft.find('a.vmenu, span.vmenudisabled, span.vmenu, a.vsmenu, span.vsmenu, a.help');
	const minimizeElement = menuLeft.find('.nsinfo-toggle-menu');
	const searchBox       = menuLeft.find('.blockvmenusearch');

	// Pas d'entrée « Réduire le menu » dans ce menu : rien à faire
	if (minimizeElement.length === 0) {
		return;
	}

	const toggleBlock = minimizeElement.closest('.blockvmenu, .blockvmenulast, .blockvmenufirst, .blockvmenupair, .blockvmenuimpair');
	toggleBlock.css('cursor', 'pointer');

	// Menu réduit (classe nsinfo-menu-minimized sur #id-left) : sous-menus sans icône et séparateurs masqués,
	// blocs resserrés, pour supprimer les espacements verticaux laissés par les textes masqués
	if ($('#nsinfo-menu-minimized-style').length === 0) {
		$('head').append('<style id="nsinfo-menu-minimized-style">'
			+ '#id-left.nsinfo-menu-minimized .menu_contenu, #id-left.nsinfo-menu-minimized .menu_top, #id-left.nsinfo-menu-minimized .menu_end { display: none !important; }'
			+ '#id-left.nsinfo-menu-minimized .blockvmenu, #id-left.nsinfo-menu-minimized .blockvmenufirst, #id-left.nsinfo-menu-minimized .blockvmenulast,'
			+ ' #id-left.nsinfo-menu-minimized .blockvmenupair, #id-left.nsinfo-menu-minimized .blockvmenuimpair, #id-left.nsinfo-menu-minimized .blockvmenuend,'
			+ ' #id-left.nsinfo-menu-minimized #blockvmenubookmarks, #id-left.nsinfo-menu-minimized .blockvmenuhelp'
			+ ' { padding-top: 2px !important; padding-bottom: 2px !important; margin-top: 0 !important; margin-bottom: 0 !important; border-top: none !important; border-bottom: none !important; }'
			+ '#id-left.nsinfo-menu-minimized #blockvmenubookmarks, #id-left.nsinfo-menu-minimized .blockvmenuhelp { display: none !important; }'
			+ '#id-left.nsinfo-menu-minimized .menu_titre { padding-top: 2px !important; padding-bottom: 2px !important; margin-top: 0 !important; margin-bottom: 0 !important; }'
			+ '</style>');
	}

	const minimizeMenu = function(animate) {
		menuElement.each(function() {
			$(this).contents().filter(function() {
				return this.nodeType === 3;
			}).wrap('<span class="nsinfo-hidden-text" style="display:none"></span>');
		});

		// Sans animation au chargement de la page (évite d'afficher un instant le menu déplié)
		if (animate === false) {
			searchBox.hide();
			menuLeft.css('width', '30px');
		} else {
			searchBox.slideUp(200);
			menuLeft.animate({width: '30px'}, 200);
		}

		// Les blocs gardent leur largeur même texte masqué : on les limite pour ne pas recouvrir le contenu de la page
		$('#id-left').css('overflow', 'hidden').addClass('nsinfo-menu-minimized');
		menuLeft.css('overflow', 'hidden');
		menuLeft.find('.menu_titre, .menu_contenu, .blockvmenu, .blockvmenupair, .blockvmenuimpair').css({'max-width': '40px', 'overflow': 'hidden'});

		minimizeElement.attr('title', minimizeElement.data('title-maximize'));
		minimizeElement.removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');

		try {
			window.localStorage.setItem('nsinfo_menu_minimized', '1');
		} catch (e) {}
	};

	const maximizeMenu = function() {
		menuElement.each(function() {
			$(this).find('span.nsinfo-hidden-text').contents().unwrap();
		});

		searchBox.slideDown(200);
		menuLeft.animate({width: '240px'}, 200, function() {
			$(this).css('width', '');	// Largeur du thème rétablie
		});

		$('#id-left').css('overflow', '').removeClass('nsinfo-menu-minimized');
		menuLeft.css('overflow', '');
		menuLeft.find('.menu_titre, .menu_contenu, .blockvmenu, .blockvmenupair, .blockvmenuimpair').css({'max-width': '', 'overflow': ''});

		minimizeElement.attr('title', minimizeElement.data('title-minimize'));
		minimizeElement.removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-left');

		try {
			window.localStorage.setItem('nsinfo_menu_minimized', '0');
		} catch (e) {}
	};

	let minimized = false;
	try {
		minimized = (window.localStorage.getItem('nsinfo_menu_minimized') === '1');
	} catch (e) {}
	if (minimized && menuLeft.width() > 50) {
		minimizeMenu(false);
	}

	// Bascule au clic sur l'entrée « Réduire le menu »
	toggleBlock.off('click.nsinfomenu').on('click.nsinfomenu', function(event) {
		event.preventDefault();
		if (menuLeft.is(':animated')) {
			return;	// Clic pendant l'animation : ignoré
		}
		if (menuLeft.width() > 50) {
			minimizeMenu();
		} else {
			maximizeMenu();
		}
	});
};
