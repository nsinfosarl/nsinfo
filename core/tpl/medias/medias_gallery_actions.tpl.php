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
 * \file    core/tpl/medias/medias_gallery_actions.tpl.php
 * \ingroup nsinfo
 * \brief   NSINFO media gallery: processes the media subactions (upload, link,
 *          favorite, unlink, delete, pagination). Must be included from the
 *          "Actions" section of the calling page, before any code that reads
 *          the linked medias from disk (nsinfo_show_medias_linked(), ...), so
 *          that page fragments returned to AJAX callers reflect the change.
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
 *
 * medias_gallery_modal.tpl.php (rendered later, in the "View" section) relies
 * on $mediaSizes, $modulepartUpper and $ecmMediasDir set below.
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
