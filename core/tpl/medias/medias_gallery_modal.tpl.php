<?php

/* Copyright (C) 2026 NSINFO <contact@ns-info.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/medias/medias_gallery_modal.tpl.php
 * \ingroup nsinfo
 * \brief   NSINFO media gallery: renders the #media_gallery modal.
 *
 * medias_gallery_actions.tpl.php MUST have already been included (from the
 * "Actions" section of the calling page, before this template) so that the
 * medias listed below reflect any subaction (addFiles, unlinkFile, ...)
 * processed for the current request.
 *
 * The following vars must be defined before including this template:
 * Global     : $conf, $db, $langs, $user
 * Parameters : $modulepart      Module owning the medias (e.g. 'gmao')
 *              $object          Current object the medias are linked to (already fetched)
 *              $ecmMediasDir    Set by medias_gallery_actions.tpl.php
 *              $modulepartUpper Set by medias_gallery_actions.tpl.php
 *
 * This template does not depend on the Saturne module. It only needs the
 * NSINFO helper libraries (lib/medias.lib.php, lib/pagination.lib.php).
 */

require_once __DIR__ . '/../../../lib/medias.lib.php';
require_once __DIR__ . '/../../../lib/pagination.lib.php';

?>
<!-- START NSINFO MEDIA GALLERY MODAL -->
<div class="wpeo-modal modal-photo" id="media_gallery" data-id="<?php echo (isset($object) && $object) ? $object->id : 0 ?>">
	<div class="modal-container wpeo-modal-event">
		<div class="modal-header">
			<h2 class="modal-title"><?php echo $langs->trans('MediaGallery') ?></h2>
			<div class="modal-close"><i class="fas fa-2x fa-times"></i></div>
		</div>
		<div class="modal-content" id="#modalMediaGalleryContent">
			<div class="messageSuccessSendPhoto notice hidden">
				<div class="wpeo-notice notice-success send-photo-success-notice">
					<div class="notice-content">
						<div class="notice-title"><?php echo $langs->trans('PhotoWellSent') ?></div>
					</div>
					<div class="notice-close"><i class="fas fa-times"></i></div>
				</div>
			</div>
			<div class="messageErrorSendPhoto notice hidden">
				<div class="wpeo-notice notice-error send-photo-error-notice">
					<div class="notice-content">
						<div class="notice-title"><?php echo $langs->trans('PhotoNotSent') ?></div>
						<div class="notice-subtitle"></div>
					</div>
					<div class="notice-close"><i class="fas fa-times"></i></div>
				</div>
			</div>
			<div class="wpeo-gridlayout grid-3">
				<div class="modal-add-media">
					<input type="hidden" name="token" value="<?php echo newToken(); ?>">
					<strong><?php echo $langs->trans('AddFile'); ?></strong>
					<input type="file" id="add_media_to_gallery" class="flat minwidth400 maxwidth200onsmartphone" name="userfile[]" multiple accept='image/*'>
					<div class="underbanner clearboth"></div>
				</div>
				<div class="form-element">
					<span class="form-label"><strong><?php echo $langs->trans('SearchFile') ?></strong></span>
					<div class="form-field-container">
						<input id="search_in_gallery" placeholder="<?php echo $langs->trans('Search') . '...' ?>" class="minwidth200" type="text">
					</div>
				</div>
				<div>
					<?php
					echo img_picto($langs->trans('Calendar'), 'calendar') . ' ' . $langs->trans('Today');
					if (!empty($user->conf->NSINFO_MEDIA_GALLERY_SHOW_TODAY_MEDIAS)) {
					    echo ' <span id="del_today_medias" value="0" class="valignmiddle linkobject toggle-today-medias">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</span>';
					} else {
					    echo ' <span id="set_today_medias" value="1" class="valignmiddle linkobject toggle-today-medias">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</span>';
					}
					?>
				</div>
			</div>
			<div id="progressBarContainer" style="display: none;">
				<div id="progressBar"></div>
			</div>
			<div class="ecm-photo-list-content">
				<?php nsinfo_show_medias($modulepart, $ecmMediasDir, 'small', 80, 80, (!empty($offset) ? $offset : 1)); ?>
			</div>
		</div>
		<div class="modal-footer">
			<?php
			$filearray = dol_dir_list($ecmMediasDir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);
			if (!empty($user->conf->NSINFO_MEDIA_GALLERY_SHOW_TODAY_MEDIAS)) {
			    $yesterdayTimeStamp = dol_time_plus_duree(dol_now(), -1, 'd');
			    $filearray          = array_filter($filearray, function ($file) use ($yesterdayTimeStamp) {
			        return $file['date'] > $yesterdayTimeStamp;
			    });
			}
			$imageNumberPerPage = getDolGlobalInt($modulepartUpper . '_DISPLAY_NUMBER_MEDIA_GALLERY', 20);
			$allMediasNumber    = count($filearray);
			$pagesCounter       = $imageNumberPerPage ? ceil($allMediasNumber / $imageNumberPerPage) : 1;
			$pageArray          = nsinfo_load_pagination($pagesCounter, $loadedPageArray ?? [], $offset ?? 0);

			echo nsinfo_show_pagination($pagesCounter, $pageArray, $offset ?? 0);
			?>
			<div class="save-photo wpeo-button button-blue button-disable" value="">
				<span><?php echo $langs->trans('Add'); ?></span>
			</div>
			<div class="wpeo-button button-red button-disable delete-photo">
				<i class="fas fa-trash-alt"></i>
			</div>
			<?php
			$confirmationParams = [
			    'picto'             => 'fontawesome_fa-trash-alt_fas_#e05353',
			    'color'             => '#e05353',
			    'confirmationTitle' => 'DeleteFiles',
			    'buttonParams'      => ['No' => 'button-blue marginrightonly confirmation-close', 'Yes' => 'button-red confirmation-delete'],
			];
			require __DIR__ . '/../utils/confirmation_view.tpl.php';
			?>
		</div>
	</div>
</div>
<!-- END NSINFO MEDIA GALLERY MODAL -->
