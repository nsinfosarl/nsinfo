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
 * \brief   NSINFO media gallery: processes the media subactions (upload, link,
 *          favorite, unlink, delete, pagination) and renders the #media_gallery modal.
 *
 * The following vars must be defined before including this template:
 * Global     : $conf, $db, $langs, $user
 * Parameters : $modulepart      Module owning the medias (e.g. 'gmao')
 *              $object          Current object the medias are linked to (already fetched)
 *              $subaction       GETPOST('subaction', 'aZ09')
 *              $permissiontoadd Right used to gate the upload/link/unlink actions
 *
 * This template does not depend on the Saturne module. It only needs the
 * NSINFO helper libraries (lib/medias.lib.php, lib/pagination.lib.php).
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once __DIR__ . '/../../../lib/medias.lib.php';
require_once __DIR__ . '/../../../lib/pagination.lib.php';

$mediaSizes = ['mini', 'small', 'medium', 'large'];

$modulepartUpper = dol_strtoupper($modulepart);
$ecmMediasDir     = $conf->ecm->multidir_output[$conf->entity] . '/' . $modulepart . '/medias';

// Upload a file into the shared staging directory (from the fast-upload button or the modal add-file input).
if (!(isset($error) && $error) && $subaction == 'uploadPhoto' && $permissiontoadd && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    if (!dol_is_dir($ecmMediasDir)) {
        dol_mkdir($ecmMediasDir);
    }

    $userfiles = is_array($_FILES['userfile']['tmp_name']) ? $_FILES['userfile']['tmp_name'] : [$_FILES['userfile']['tmp_name']];

    foreach ($userfiles as $key => $userfile) {
        $error = 0;
        if (empty($_FILES['userfile']['tmp_name'][$key])) {
            $error++;
            setEventMessages($langs->transnoentitiesnoconv('ErrorThisFileSizeTooLarge', (string) ($_FILES['userfile']['name'][$key] ?? '')), null, 'errors');
            $submitFileErrorText = ['message' => $langs->transnoentitiesnoconv('ErrorThisFileSizeTooLarge', (string) ($_FILES['userfile']['name'][$key] ?? '')), 'code' => '1337'];
        }

        if (!$error) {
            $res = dol_add_file_process($ecmMediasDir, 0, 1, 'userfile', '', null, '', 0);
            if ($res > 0) {
                $fileName        = $_FILES['userfile']['name'][$key];
                $sizeMedium      = nsinfo_get_media_thumb_size($modulepart, 'medium');
                $sizeLarge       = nsinfo_get_media_thumb_size($modulepart, 'large');
                nsinfo_vignette($ecmMediasDir . '/' . $fileName, $sizeLarge['width'], $sizeLarge['height'], '_large');
                nsinfo_vignette($ecmMediasDir . '/' . $fileName, $sizeMedium['width'], $sizeMedium['height'], '_medium');
            } else {
                setEventMessages($langs->transnoentitiesnoconv('ErrorThisFileExists', (string) ($_FILES['userfile']['name'][$key] ?? ''), $langs->transnoentitiesnoconv('File')), null, 'errors');
                $submitFileErrorText = ['message' => $langs->transnoentities('ErrorThisFileExists', (string) ($_FILES['userfile']['name'][$key] ?? '')), 'code' => '1337'];
            }
        }
    }
}

// Link one or more staged files to the current object (from the fast-upload button or the "Add" button in the gallery modal).
if ($subaction == 'addFiles' && $permissiontoadd && is_object($object)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $objectSubtype = $data['objectSubtype'] ?? 'photo';
    $objectSubdir  = $data['objectSubdir']  ?? '';

    $pathToObjectImg = $conf->$modulepart->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref . (!empty($objectSubdir) ? '/' . $objectSubdir : '');
    if (!dol_is_dir($pathToObjectImg)) {
        dol_mkdir($pathToObjectImg);
    }

    $fileNames = [];
    if (!empty($data['filenames'])) {
        if (strpos($data['filenames'], 'vVv') !== false) {
            $fileNames = explode('vVv', $data['filenames']);
            array_pop($fileNames);
        } else {
            $fileNames = [$data['filenames']];
        }
    }

    if (!empty($fileNames)) {
        foreach ($fileNames as $fileName) {
            $fileName = dol_sanitizeFileName($fileName);
            if (empty($object->$objectSubtype)) {
                $object->$objectSubtype = $fileName;
            }

            dol_copy($ecmMediasDir . '/' . $fileName, $pathToObjectImg . '/' . $fileName);

            foreach ($mediaSizes as $size) {
                $thumbSize = nsinfo_get_media_thumb_size($modulepart, $size);
                nsinfo_vignette($pathToObjectImg . '/' . $fileName, $thumbSize['width'], $thumbSize['height'], '_' . $size);
            }
        }

        if ($object->id > 0 && property_exists($object, $objectSubtype)) {
            $object->setValueFrom($objectSubtype, $object->$objectSubtype, '', '', 'text', '', $user);
        }
    }
}

// Remove one or more staged files from the shared gallery (multi-select delete in the modal footer).
if ($subaction == 'delete_files' && $permissiontodelete) {
    $data = json_decode(file_get_contents('php://input'), true);

    $fileNames = [];
    if (!empty($data['filenames'])) {
        if (strpos($data['filenames'], 'vVv') !== false) {
            $fileNames = explode('vVv', $data['filenames']);
            array_pop($fileNames);
        } else {
            $fileNames = [$data['filenames']];
        }
    }

    foreach ($fileNames as $fileName) {
        $fileName       = dol_sanitizeFileName($fileName);
        $pathToECMPhoto = $ecmMediasDir . '/' . $fileName;
        if (is_file($pathToECMPhoto)) {
            foreach ($mediaSizes as $size) {
                $thumbName = $ecmMediasDir . '/thumbs/' . nsinfo_get_thumb_name($fileName, $modulepart, $size);
                if (is_file($thumbName)) {
                    unlink($thumbName);
                }
            }
            unlink($pathToECMPhoto);
        }
    }
}

// Unlink a file from the current object (does not delete it from the shared staging gallery).
if ($subaction == 'unlinkFile' && $permissiontodelete && is_object($object)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $objectSubtype = $data['objectSubtype'] ?? 'photo';
    $filepath      = $data['filepath'] ?? '';
    $filename      = dol_sanitizeFileName($data['filename'] ?? '');

    $fullPath = $filepath . '/' . $filename;
    if (!empty($filename) && is_file($fullPath)) {
        unlink($fullPath);

        foreach ($mediaSizes as $size) {
            $thumbName = $filepath . '/thumbs/' . nsinfo_get_thumb_name($filename, $modulepart, $size);
            if (is_file($thumbName)) {
                unlink($thumbName);
            }
        }
    }

    if ($object->id > 0 && ($object->$objectSubtype ?? null) == $filename) {
        $fileArray = dol_dir_list($filepath, 'files');
        $object->$objectSubtype = count($fileArray) > 0 ? reset($fileArray)['name'] : '';
        $object->setValueFrom($objectSubtype, $object->$objectSubtype, '', '', 'text', '', $user);
    }
}

// Set a file already linked to the object as its favorite (main) photo.
if ($subaction == 'addToFavorite' && $permissiontoadd && is_object($object)) {
    $data = json_decode(file_get_contents('php://input'), true);

    $objectSubtype = $data['objectSubtype'] ?? 'photo';
    $filename      = dol_sanitizeFileName($data['filename'] ?? '');

    if ($object->id > 0 && property_exists($object, $objectSubtype)) {
        $object->$objectSubtype = $filename;
        $object->setValueFrom($objectSubtype, $object->$objectSubtype, '', '', 'text', '', $user);
    }
}

// Recompute the pagination array (used when clicking a page number in the modal footer).
if (!(isset($error) && $error) && $subaction == 'pagination') {
    $data = json_decode(file_get_contents('php://input'), true);

    $offset          = (int) ($data['offset'] ?? 1);
    $pagesCounter    = (float) ($data['pagesCounter'] ?? 1);
    $loadedPageArray = nsinfo_load_pagination($pagesCounter, [], $offset);
}

// Toggle the "today's medias only" filter (stored as a per-user preference).
if (!(isset($error) && $error) && $subaction == 'toggleTodayMedias') {
    $toggleValue                                      = GETPOST('toggle_today_medias');
    $tabparam['NSINFO_MEDIA_GALLERY_SHOW_TODAY_MEDIAS'] = $toggleValue;

    dol_set_user_param($db, $conf, $user, $tabparam);
}

// Force regenerate a missing thumb (clicked from the gallery grid when a thumb could not be found).
if (!(isset($error) && $error) && $subaction == 'regenerate_thumbs') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data['fullname'])) {
        foreach ($mediaSizes as $size) {
            $thumbSize = nsinfo_get_media_thumb_size($modulepart, $size);
            nsinfo_vignette($data['fullname'], $thumbSize['width'], $thumbSize['height'], '_' . $size);
        }
    }
}

if (!empty($submitFileErrorText) && is_array($submitFileErrorText)) {
    print '<input class="error-medias" value="' . htmlspecialchars(json_encode($submitFileErrorText)) . '">';
}

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
