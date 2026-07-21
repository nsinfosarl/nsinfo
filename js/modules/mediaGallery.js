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
 * \file    js/modules/mediaGallery.js
 * \ingroup nsinfo
 * \brief   JavaScript media gallery for module NSINFO.
 *
 * Talks to core/tpl/medias/medias_gallery_modal.tpl.php (included by the calling
 * page) via subaction=... POST requests sent back to the current page URL.
 */

window.nsinfo.mediaGallery = {};

window.nsinfo.mediaGallery.init = function() {
	window.nsinfo.mediaGallery.event();
};

window.nsinfo.mediaGallery.event = function() {
	$(document).on('click', '.clickable-photo', window.nsinfo.mediaGallery.selectPhoto);
	$(document).on('click', '.save-photo', window.nsinfo.mediaGallery.savePhoto);
	$(document).on('click', '.delete-photo', window.nsinfo.mediaGallery.deletePhoto);
	$(document).on('change', '.flat.minwidth400.maxwidth200onsmartphone', window.nsinfo.mediaGallery.sendPhoto);
	$(document).on('click', '.clicked-photo-preview', window.nsinfo.mediaGallery.previewPhoto);
	$(document).on('input', '.form-element #search_in_gallery', window.nsinfo.mediaGallery.handleSearch);
	$(document).on('click', '.media-gallery-unlink', window.nsinfo.mediaGallery.unlinkFile);
	$(document).on('click', '.media-gallery-favorite', window.nsinfo.mediaGallery.addToFavorite);
	$(document).on('change', '.fast-upload', window.nsinfo.mediaGallery.fastUpload);
	$(document).on('click', '.select-page', window.nsinfo.mediaGallery.selectPage);
	$(document).on('click', '.toggle-today-medias', window.nsinfo.mediaGallery.toggleTodayMedias);
	$(document).on('click', '.regenerate-thumbs', window.nsinfo.mediaGallery.regenerateThumbs);
};

/**
 * Select/deselect a photo in the gallery grid (multi-select for link/delete).
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.selectPhoto = function() {
	let photoID = $(this).attr('value');
	let parent  = $(this).closest('.modal-content');

	if ($(this).hasClass('clicked-photo')) {
		$(this).attr('style', 'none !important');
		$(this).removeClass('clicked-photo');
		if ($('.clicked-photo').length === 0) {
			$(this).closest('.modal-container').find('.save-photo').addClass('button-disable');
			$(this).closest('.modal-container').find('.delete-photo').addClass('button-disable');
		}
	} else {
		parent.closest('.modal-container').find('.save-photo').removeClass('button-disable');
		parent.closest('.modal-container').find('.delete-photo').removeClass('button-disable');
		parent.find('.clickable-photo' + photoID).addClass('clicked-photo');
	}
};

/**
 * Link the selected gallery photo(s) to the current object.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.savePhoto = function() {
	let mediaGallery      = $('#media_gallery');
	let mediaGalleryModal = $(this).closest('.modal-container');
	let filesLinked       = mediaGalleryModal.find('.clicked-photo');
	let token             = window.nsinfo.toolbox.getToken();

	let objectSubtype    = mediaGallery.attr('data-from-subtype');
	let objectSubdir     = mediaGallery.attr('data-from-subdir');
	let objectPhotoClass = mediaGallery.attr('data-photo-class');

	let filenames = '';
	if (filesLinked.length > 0) {
		filesLinked.each(function() {
			filenames += $(this).find('.filename').val() + 'vVv';
		});
	}

	window.nsinfo.loader.display($(this));
	if (typeof objectPhotoClass !== 'undefined' && objectPhotoClass.length > 0) {
		if ($('.linked-medias.' + objectPhotoClass).length > 0) {
			window.nsinfo.loader.display($('.linked-medias.' + objectPhotoClass));
		}
	} else {
		window.nsinfo.loader.display($('.linked-medias.' + objectSubtype));
	}

	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	$.ajax({
		url: document.URL + querySeparator + 'subaction=addFiles&token=' + token,
		type: 'POST',
		data: JSON.stringify({
			filenames: filenames,
			objectSubdir: objectSubdir,
			objectSubtype: objectSubtype
		}),
		processData: false,
		contentType: false,
		success: function(resp) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			mediaGallery.removeClass('modal-active');
			if (typeof objectPhotoClass !== 'undefined' && objectPhotoClass.length > 0) {
				$('.photo.' + objectPhotoClass).replaceWith($(resp).find('.photo.' + objectPhotoClass).first());
				$('.linked-medias.' + objectPhotoClass).replaceWith($(resp).find('.linked-medias.' + objectPhotoClass));
			} else {
				$('.linked-medias.' + objectSubtype).html($(resp).find('.linked-medias.' + objectSubtype).children());
			}
			mediaGallery.html($(resp).find('#media_gallery').children());
			window.nsinfo.modal.loadLazyImages();
		}
	});
};

/**
 * Delete the selected photo(s) from the shared gallery, after confirmation.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.deletePhoto = function() {
	let mediaGalleryModal = $(this).closest('.modal-container');
	let filesLinked       = mediaGalleryModal.find('.clicked-photo');
	let fileNames         = '';
	if (filesLinked.length > 0) {
		filesLinked.each(function() {
			fileNames += $(this).find('.filename').val() + 'vVv';
		});
	}
	window.nsinfo.loader.display($(this));

	$('.card__confirmation').removeAttr('style');
	$(document).off('click', '.confirmation-close').on('click', '.confirmation-close', function() {
		window.nsinfo.mediaGallery.closeConfirmation(filesLinked, mediaGalleryModal);
	});
	$(document).off('click', '.confirmation-delete').on('click', '.confirmation-delete', function() {
		window.nsinfo.mediaGallery.deleteFilesRequest(fileNames);
	});
};

/**
 * Hide the confirmation box without deleting anything.
 *
 * @param  {jQuery} filesLinked       Selected photo elements
 * @param  {jQuery} mediaGalleryModal Media gallery modal element
 * @return {void}
 */
window.nsinfo.mediaGallery.closeConfirmation = function(filesLinked, mediaGalleryModal) {
	$('.wpeo-loader').removeClass('wpeo-loader');
	$('.card__confirmation').attr('style', 'display:none;');
	if (filesLinked.length > 0) {
		filesLinked.each(function() {
			filesLinked.removeClass('clicked-photo');
		});
	}
	mediaGalleryModal.find('.save-photo').addClass('button-disable');
	mediaGalleryModal.find('.delete-photo').addClass('button-disable');
};

/**
 * Send the delete_files request for the confirmed file names.
 *
 * @param  {string} fileNames 'vVv'-separated file names
 * @return {void}
 */
window.nsinfo.mediaGallery.deleteFilesRequest = function(fileNames) {
	let token          = window.nsinfo.toolbox.getToken();
	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	$.ajax({
		url: document.URL + querySeparator + 'subaction=delete_files&token=' + token,
		type: 'POST',
		processData: false,
		contentType: false,
		data: JSON.stringify({
			filenames: fileNames
		}),
		success: function(resp) {
			$('#media_gallery .modal-container').replaceWith($(resp).find('#media_gallery .modal-container'));
			window.nsinfo.modal.loadLazyImages();
		}
	});
};

/**
 * Client-side filter of the gallery grid by filename (no server round-trip).
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.handleSearch = function() {
	let searchQuery = $('#search_in_gallery').val();
	let photos      = $('.center.clickable-photo');

	photos.each(function() {
		$(this).text().trim().match(searchQuery) ? $(this).show() : $(this).hide();
	});
};

/**
 * Upload photo(s) selected from the modal's "Add file" input into the shared gallery.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.sendPhoto = function(event) {
	event.preventDefault();

	let mediaGallery = $('#media_gallery');
	let files        = $(this).prop('files');

	let totalCount        = files.length;
	let requestCompleted  = 0;
	let progress          = 0;

	let objectId      = mediaGallery.attr('data-from-id');
	let objectType    = mediaGallery.attr('data-from-type');
	let objectSubtype = mediaGallery.attr('data-from-subtype');
	let objectSubdir  = mediaGallery.attr('data-from-subdir');

	let token = window.nsinfo.toolbox.getToken();

	$('#progressBar').width(0);
	$('#progressBarContainer').attr('style', 'display:block');

	window.nsinfo.loader.display($('#progressBarContainer'));

	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);
	let textToShow = '';

	$.each(files, function(index, file) {
		let formdata = new FormData();
		formdata.append('userfile[]', file);
		$.ajax({
			url: document.URL + querySeparator + 'subaction=uploadPhoto&token=' + token,
			type: 'POST',
			data: formdata,
			processData: false,
			contentType: false,
			success: function(resp) {
				requestCompleted++;
				progress += (1 / totalCount) * 100;
				$('#progressBar').animate({width: progress + '%'}, 1);
				if ($(resp).find('.error-medias').length) {
					let errorMessage = $(resp).find('.error-medias').val();
					let decodedErrorMessage = JSON.parse(errorMessage);
					textToShow += decodedErrorMessage.message + '<br>';
				}

				if (requestCompleted === totalCount) {
					$('.wpeo-loader').removeClass('wpeo-loader');
					$('#progressBarContainer').fadeOut(800);
					$('#progressBarContainer').find('.loader-spin').remove();
					window.nsinfo.loader.display(mediaGallery.find('.ecm-photo-list-content'));
					setTimeout(() => {
						mediaGallery.html($(resp).find('#media_gallery').children()).promise().done(() => {
							if (totalCount === 1) {
								$('#media_gallery').find('.save-photo').removeClass('button-disable');
								$('#media_gallery').find('.delete-photo').removeClass('button-disable');
								$('#media_gallery').find('.clickable-photo0').addClass('clicked-photo');
							}
							if ($(resp).find('.error-medias').length) {
								$('.messageErrorSendPhoto').find('.notice-subtitle').html(textToShow);
								$('.messageErrorSendPhoto').removeClass('hidden');
							} else {
								$('.messageSuccessSendPhoto').removeClass('hidden');
							}
							mediaGallery.attr('data-from-id', objectId);
							mediaGallery.attr('data-from-type', objectType);
							mediaGallery.attr('data-from-subtype', objectSubtype);
							mediaGallery.attr('data-from-subdir', objectSubdir);
							mediaGallery.find('.wpeo-button').attr('value', objectId);
							window.nsinfo.modal.loadLazyImages();
						});
					}, 800);
				}
			}
		});
	});
};

/**
 * Mark the Dolibarr lightbox opened from a photo preview link so it can be CSS-styled.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.previewPhoto = function() {
	let checkExist = setInterval(function() {
		if ($('.ui-dialog').length) {
			clearInterval(checkExist);
			$(document).find('.ui-dialog').addClass('preview-photo');
		}
	}, 100);
};

/**
 * Unlink a photo from the current object (does not delete it from the shared gallery).
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.unlinkFile = function(event) {
	event.preventDefault();

	let token = window.nsinfo.toolbox.getToken();

	let modal   = $(this).closest('.modal-active');
	let inModal = $(this).closest('.modal-active').length > 0;

	let mediaInfos = inModal ? modal.find('.modal-options') : $(this).closest('.linked-medias').find('.modal-options');

	let objectSubtype = mediaInfos.attr('data-from-subtype');

	let mediaContainer = $(this).closest('.media-container');
	let filepath       = mediaContainer.find('.file-path').val();
	let filename       = mediaContainer.find('.file-name').val();

	window.nsinfo.loader.display(mediaContainer);

	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	$('.card__confirmation').css('display', 'flex');
	$(document).on('click', '.confirmation-close', function() {
		$(document).off('click', '.confirmation-delete');
		$(document).off('click', '.confirmation-close');
		$('.wpeo-loader').removeClass('wpeo-loader');
		$('.card__confirmation').css('display', 'none');
	});
	$(document).on('click', '.confirmation-delete', function() {
		$(document).off('click', '.confirmation-delete');
		$(document).off('click', '.confirmation-close');

		window.nsinfo.loader.display($(this));

		$.ajax({
			url: document.URL + querySeparator + 'subaction=unlinkFile&token=' + token,
			type: 'POST',
			data: JSON.stringify({
				filepath: filepath,
				filename: filename,
				objectSubtype: objectSubtype
			}),
			processData: false,
			contentType: 'application/json',
			success: function() {
				// Reload so the object's linked-photo section (rendered server-side) picks up the removal.
				window.location.reload();
			}
		});
	});
};

/**
 * Mark a photo already linked to the object as its favorite (main) photo.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.addToFavorite = function(event) {
	event.preventDefault();
	let filename = $(this).closest('.media-gallery-favorite').find('.filename').attr('value');

	let token = window.nsinfo.toolbox.getToken();

	let modal   = $(this).closest('.modal-active');
	let inModal = $(this).closest('.modal-active').length > 0;

	let previousFavorite = $(this).closest('.linked-medias').find('.fas.fa-star');
	let newFavorite      = $(this).find('.far.fa-star');

	let mediaInfos = inModal ? modal.find('.modal-options') : $(this).closest('.linked-medias').find('.modal-options');

	let objectSubtype    = mediaInfos.attr('data-from-subtype');
	let objectPhotoClass = mediaInfos.attr('data-photo-class');

	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	previousFavorite.removeClass('fas').addClass('far');
	previousFavorite.closest('.media-gallery-favorite').removeClass('favorite');
	newFavorite.addClass('fas').removeClass('far');
	newFavorite.closest('.media-gallery-favorite').addClass('favorite');

	if (filename.length > 0) {
		$(this).closest('.linked-medias').find('.favorite-photo').val(filename);
	}

	if (typeof objectPhotoClass !== 'undefined' && objectPhotoClass.length > 0) {
		window.nsinfo.loader.display($('.photo.' + objectPhotoClass));
	}

	$.ajax({
		url: document.URL + querySeparator + 'subaction=addToFavorite&token=' + token,
		type: 'POST',
		data: JSON.stringify({
			filename: filename,
			objectSubtype: objectSubtype
		}),
		processData: false,
		contentType: 'application/json',
		success: function(resp) {
			if (typeof objectPhotoClass !== 'undefined' && objectPhotoClass.length > 0) {
				$('.photo.' + objectPhotoClass).replaceWith($(resp).find('.photo.' + objectPhotoClass).first());
			}
			window.nsinfo.modal.loadLazyImages();
		}
	});
};

/**
 * Upload photo(s) selected from the direct camera/computer button, then link them to the object.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.fastUpload = function() {
	let files = $(this).prop('files');
	let token = window.nsinfo.toolbox.getToken();

	let objectSubtype    = $(this).closest('.linked-medias').find('.modal-options').attr('data-from-subtype');
	let objectSubdir     = $(this).closest('.linked-medias').find('.modal-options').attr('data-from-subdir');
	let objectPhotoClass = $(this).closest('.linked-medias').find('.modal-options').attr('data-photo-class');

	if (typeof objectPhotoClass !== 'undefined' && objectPhotoClass.length > 0) {
		window.nsinfo.loader.display($('.linked-medias.' + objectPhotoClass));
	} else {
		window.nsinfo.loader.display($('.linked-medias.' + objectSubtype));
	}

	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	let filenames      = '';
	let totalCount      = files.length;
	let requestCompleted = 0;

	$.each(files, function(index, file) {
		let formdata = new FormData();
		formdata.append('userfile[]', file);
		filenames += file.name + 'vVv';

		$.ajax({
			url: document.URL + querySeparator + 'subaction=uploadPhoto&token=' + token,
			type: 'POST',
			data: formdata,
			processData: false,
			contentType: false,
			complete: function() {
				requestCompleted++;
				if (requestCompleted === totalCount) {
					$.ajax({
						url: document.URL + querySeparator + 'subaction=addFiles&token=' + token,
						type: 'POST',
						data: JSON.stringify({
							filenames: filenames,
							objectSubtype: objectSubtype,
							objectSubdir: objectSubdir
						}),
						processData: false,
						contentType: false,
						success: function() {
							// Reload so the object's linked-photo section (rendered server-side) picks up the new file.
							window.location.reload();
						}
					});
				}
			}
		});

		formdata = new FormData();
	});
};

/**
 * Load a gallery page.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.selectPage = function() {
	let token = window.nsinfo.toolbox.getToken();

	let pagesCounter       = $(this).closest('.wpeo-pagination').find('#pagesCounter').val();
	let containerToRefresh = $(this).closest('.wpeo-pagination').find('#containerToRefresh').val();

	let offset = $(this).attr('value');

	let mediaGallery = $('#' + containerToRefresh);

	let objectSubtype = mediaGallery.find('.modal-options').attr('data-from-subtype');
	let objectSubdir  = mediaGallery.find('.modal-options').attr('data-from-subdir');

	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	if (!$(this).hasClass('arrow')) {
		$(this).closest('.wpeo-pagination').find('.pagination-element').removeClass('pagination-current');
		$(this).closest('.pagination-element').addClass('pagination-current');
	}

	window.nsinfo.loader.display($('#media_gallery').find('.modal-content'));

	$.ajax({
		url: document.URL + querySeparator + 'subaction=pagination&token=' + token,
		type: 'POST',
		data: JSON.stringify({offset: offset, pagesCounter: pagesCounter}),
		processData: false,
		contentType: false,
		success: function(resp) {
			$('.wpeo-loader').removeClass('wpeo-loader');
			mediaGallery.html($(resp).find('#' + containerToRefresh).children());

			mediaGallery.find('.modal-options').attr('data-from-subtype', objectSubtype);
			mediaGallery.find('.modal-options').attr('data-from-subdir', objectSubdir);
			window.nsinfo.modal.loadLazyImages();
		}
	});
};

/**
 * Toggle the "today's medias only" filter.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.toggleTodayMedias = function() {
	let token          = window.nsinfo.toolbox.getToken();
	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);

	let toggleValue = $(this).attr('value');

	window.nsinfo.loader.display($('.ecm-photo-list-content'));
	window.nsinfo.loader.display($('.wpeo-pagination'));

	$.ajax({
		url: document.URL + querySeparator + 'subaction=toggleTodayMedias&toggle_today_medias=' + toggleValue + '&token=' + token,
		type: 'POST',
		processData: false,
		contentType: false,
		success: function(resp) {
			$('.toggle-today-medias').replaceWith($(resp).find('.toggle-today-medias'));
			$('.ecm-photo-list-content').replaceWith($(resp).find('.ecm-photo-list-content'));
			$('.wpeo-pagination').replaceWith($(resp).find('.wpeo-pagination'));
			window.nsinfo.modal.loadLazyImages();
		}
	});
};

/**
 * Force-regenerate a missing thumb for a gallery grid item.
 *
 * @return {void}
 */
window.nsinfo.mediaGallery.regenerateThumbs = function() {
	let token          = window.nsinfo.toolbox.getToken();
	let querySeparator = window.nsinfo.toolbox.getQuerySeparator(document.URL);
	let fullname       = $(this).closest('.photo-image').find('.fullname').attr('data-fullname');

	window.nsinfo.loader.display($(this).closest('.photo-image'));

	$.ajax({
		url: document.URL + querySeparator + 'subaction=regenerate_thumbs&token=' + token,
		type: 'POST',
		data: JSON.stringify({fullname: fullname}),
		processData: false,
		contentType: false,
		success: function(resp) {
			$('.ecm-photo-list-content').replaceWith($(resp).find('.ecm-photo-list-content'));
			window.nsinfo.modal.loadLazyImages();
		}
	});
};
