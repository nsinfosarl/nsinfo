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
 * \file    js/modules/modal.js
 * \ingroup nsinfo
 * \brief   JavaScript generic modal handling for module NSINFO.
 */

window.nsinfo.modal = {};

window.nsinfo.modal.init = function() {
	window.nsinfo.modal.event();
};

window.nsinfo.modal.event = function() {
	$(document).on('click', '.modal-close, .modal-active:not(.modal-container):not(.modal-close-only-with-button)', window.nsinfo.modal.closeModal);
	$(document).on('click', '.modal-open', window.nsinfo.modal.openModal);
	$(document).on('click', '.modal-refresh', window.nsinfo.modal.refreshModal);
};

/**
 * Open modal referenced by the clicked element's .modal-options data attributes.
 *
 * @return {void}
 */
window.nsinfo.modal.openModal = function() {
	let modalOptions = $(this).find('.modal-options');
	let modalToOpen  = modalOptions.attr('data-modal-to-open');

	let fromId      = modalOptions.attr('data-from-id');
	let fromType    = modalOptions.attr('data-from-type');
	let fromSubtype = modalOptions.attr('data-from-subtype');
	let fromSubdir  = modalOptions.attr('data-from-subdir');
	let photoClass  = modalOptions.attr('data-photo-class');

	let urlWithoutTag = document.URL.match(/#/) ? document.URL.split(/#/)[0] : document.URL;
	history.pushState({path: document.URL}, '', urlWithoutTag);

	$('#' + modalToOpen).attr('data-from-id', fromId);
	$('#' + modalToOpen).attr('data-from-type', fromType);
	$('#' + modalToOpen).attr('data-from-subtype', fromSubtype);
	$('#' + modalToOpen).attr('data-from-subdir', fromSubdir);
	$('#' + modalToOpen).attr('data-photo-class', photoClass);

	window.nsinfo.modal.loadLazyImages();

	$('#' + modalToOpen).find('.wpeo-button').attr('value', fromId);
	$('#' + modalToOpen).addClass('modal-active');

	$('.notice').addClass('hidden');
};

/**
 * Close the currently active modal.
 *
 * @param  {MouseEvent} event Click event
 * @return {void}
 */
window.nsinfo.modal.closeModal = function(event) {
	if ($('input:focus').length < 1 && $('textarea:focus').length < 1) {
		if ($(event.target).hasClass('modal-active') || $(event.target).hasClass('modal-close') || $(event.target).parent().hasClass('modal-close')) {
			$(this).closest('.modal-active').removeClass('modal-active');
			$('.clicked-photo').attr('style', '');
			$('.clicked-photo').removeClass('clicked-photo');
			$('.notice').addClass('hidden');
		}
	}
};

/**
 * Reload the current page (used by .modal-refresh triggers).
 *
 * @return {void}
 */
window.nsinfo.modal.refreshModal = function() {
	window.location.reload();
};

/**
 * Lazy load images inside the modal: sets `src` from `data-src` on `.photo` elements.
 *
 * Call this after the modal is opened, or after replacing its content with an AJAX response.
 *
 * @return {void}
 */
window.nsinfo.modal.loadLazyImages = function() {
	$('.photo[data-src]').each(function() {
		const $img = $(this);
		if (!$img.attr('data-loaded')) {
			$img.attr('src', $img.data('src'));
			$img.attr('data-loaded', 'true');
		}
	});
};
