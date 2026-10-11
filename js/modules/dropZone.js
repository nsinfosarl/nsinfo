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
 * \file    js/modules/dropZone.js
 * \ingroup nsinfo
 * \brief   Zone de dépôt de fichiers (nsinfoDropZone() de lib/nsinfo.lib.php) : glisser-déposer ou clic pour choisir,
 *          les fichiers sont envoyés aussitôt par le formulaire de la zone. Événements délégués : plusieurs zones par page.
 */

if (!window.nsinfo) {
	window.nsinfo = {};
}

if (!window.nsinfo.dropZone) {
	window.nsinfo.dropZone = {};

	/**
	 * Envoi du formulaire de la zone, avec le message d'envoi en cours
	 *
	 * @param {jQuery} zone Zone de dépôt
	 * @return {void}
	 */
	window.nsinfo.dropZone.send = function(zone) {
		zone.addClass('nsinfo-dropzone-sending').find('.nsinfo-dropzone-label').text(zone.attr('data-sending'));
		zone.closest('form').trigger('submit');
	};

	$(document).on('click keydown', '.nsinfo-dropzone:not(.nsinfo-dropzone-disabled)', function(event) {
		if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
			return;
		}
		event.preventDefault();
		$(this).closest('form').find('.nsinfo-dropzone-input').trigger('click');
	});

	$(document).on('change', '.nsinfo-dropzone-input', function() {
		if (this.files && this.files.length) {
			window.nsinfo.dropZone.send($(this).closest('form').find('.nsinfo-dropzone'));
		}
	});

	$(document).on('dragenter dragover', '.nsinfo-dropzone:not(.nsinfo-dropzone-disabled)', function(event) {
		event.preventDefault();
		event.stopPropagation();
		$(this).addClass('nsinfo-dropzone-over');
	});

	$(document).on('dragleave', '.nsinfo-dropzone', function(event) {
		// Sortie de la zone (et non passage sur un élément intérieur)
		if (!this.contains(event.relatedTarget)) {
			$(this).removeClass('nsinfo-dropzone-over');
		}
	});

	$(document).on('drop', '.nsinfo-dropzone:not(.nsinfo-dropzone-disabled)', function(event) {
		event.preventDefault();
		event.stopPropagation();
		const zone = $(this).removeClass('nsinfo-dropzone-over');
		const dt = event.originalEvent ? event.originalEvent.dataTransfer : null;
		if (!dt || !dt.files || !dt.files.length) {
			return;
		}
		const input = zone.closest('form').find('.nsinfo-dropzone-input').get(0);
		input.files = dt.files;
		window.nsinfo.dropZone.send(zone);
	});
}
