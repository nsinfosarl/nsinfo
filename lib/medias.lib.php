<?php
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
 * \file    lib/medias.lib.php
 * \ingroup nsinfo
 * \brief   Fichiers de bibliothèque avec les fonctions communes aux médias NSINFO
 */

include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

/**
 * Largeur/hauteur max par défaut (px) utilisées pour générer une vignette de média quand aucune
 * constante de réglage {MODULEPART}_MEDIA_MAX_WIDTH_{SIZE} / _HEIGHT_ n'est définie.
 */
const NSINFO_MEDIA_DEFAULT_THUMB_SIZES = [
    'mini'   => ['width' => 100, 'height' => 100],
    'small'  => ['width' => 200, 'height' => 200],
    'medium' => ['width' => 500, 'height' => 500],
    'large'  => ['width' => 1000, 'height' => 1000],
];

/**
 * Renvoie la largeur/hauteur max configurée (ou par défaut) pour une taille de vignette, pour un module donné.
 *
 * @param  string $modulepart Nom du module (ex. 'gmao')
 * @param  string $size       Taille de vignette (mini, small, medium, large)
 * @return array              ['width' => int, 'height' => int]
 */
function nsinfo_get_media_thumb_size(string $modulepart, string $size): array
{
    $defaults = NSINFO_MEDIA_DEFAULT_THUMB_SIZES[$size] ?? NSINFO_MEDIA_DEFAULT_THUMB_SIZES['small'];

    return [
        'width'  => getDolGlobalInt(dol_strtoupper($modulepart) . '_MEDIA_MAX_WIDTH_' . dol_strtoupper($size), $defaults['width']),
        'height' => getDolGlobalInt(dol_strtoupper($modulepart) . '_MEDIA_MAX_HEIGHT_' . dol_strtoupper($size), $defaults['height']),
    ];
}

/**
 * Génère une vignette pour un fichier média (simple enveloppe de la fonction native vignette() de Dolibarr).
 *
 * @param  string $file      Chemin complet du fichier source
 * @param  int    $maxWidth  Largeur maximale de la vignette
 * @param  int    $maxHeight Hauteur maximale de la vignette
 * @param  string $extName   Suffixe de la vignette générée (ex. '_small')
 * @return string             Chemin complet de la vignette générée, ou message d'erreur
 */
function nsinfo_vignette(string $file, int $maxWidth = 160, int $maxHeight = 120, string $extName = '_small')
{
    require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

    return vignette($file, $maxWidth, $maxHeight, $extName);
}

/**
 * Renvoie le nom de la vignette du fichier, en la générant si elle n'existe pas encore.
 *
 * @param  string $filename   Nom du fichier
 * @param  string $modulepart Nom du module, utilisé pour lire la configuration {MODULE}_MEDIA_MAX_WIDTH/HEIGHT_{SIZE}
 * @param  string $thumbType  Type de vignette (mini, small, medium, large)
 * @param  string $filePath   Répertoire contenant le fichier
 * @return string|int         Nom de fichier de la vignette, ou -1 en cas d'erreur
 */
function nsinfo_get_thumb_name(string $filename, string $modulepart, string $thumbType = 'small', string $filePath = '')
{
    $fileName      = pathinfo($filename, PATHINFO_FILENAME);
    $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
    $thumbFileName = $fileName . '_' . $thumbType . '.' . $fileExtension;

    if (!empty($filePath)) {
        $filePathThumb = $filePath . '/thumbs';
        if (!dol_is_dir($filePathThumb)) {
            dol_mkdir($filePathThumb);
        }

        if (dol_is_file($filePathThumb . '/' . $thumbFileName)) {
            return $thumbFileName;
        }

        // vignette() renvoie le chemin complet de la vignette générée (ou une chaîne d'erreur), pas seulement son nom.
        // On vérifie seulement si la génération a réussi : on contrôle ensuite le nom de base attendu.
        $thumbSize = nsinfo_get_media_thumb_size($modulepart, $thumbType);
        nsinfo_vignette($filePath . '/' . $filename, $thumbSize['width'], $thumbSize['height'], '_' . $thumbType);

        return dol_is_file($filePathThumb . '/' . $thumbFileName) ? $thumbFileName : -1;
    }

    return $fileName . '_' . $thumbType . '.' . $fileExtension;
}

/**
 * Affiche les médias de la galerie partagée du module (grille de tous les médias, avec pagination).
 *
 * @param  string $modulepart Nom du module
 * @param  string $sdir       Chemin du répertoire contenant les fichiers de transit de la galerie
 * @param  string $size       Taille des médias (mini ou small)
 * @param  int    $maxHeight  Media max height
 * @param  int    $maxWidth   Media max width
 * @param  int    $offset     Media gallery offset page
 */
function nsinfo_show_medias(string $modulepart, string $sdir = '', string $size = 'small', int $maxHeight = 80, int $maxWidth = 80, int $offset = 1): void
{
    global $conf, $langs, $user;

    $sortfield = 'date';
    $sortorder = 'desc';
    $dir       = $sdir . '/';

    $nbphoto = 0;

    $filearray = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC));

    if (!empty($user->conf->NSINFO_MEDIA_GALLERY_SHOW_TODAY_MEDIAS)) {
        $yesterdayTimeStamp = dol_time_plus_duree(dol_now(), -1, 'd');
        $filearray          = array_filter($filearray, function ($file) use ($yesterdayTimeStamp) {
            return $file['date'] > $yesterdayTimeStamp;
        });
    }

    if ($sortfield && $sortorder) {
        $filearray = dol_sort_array($filearray, $sortfield, $sortorder);
    }
    $filearray = array_values($filearray);

    $imageNumberPerPage = getDolGlobalInt(dol_strtoupper($modulepart) . '_DISPLAY_NUMBER_MEDIA_GALLERY', 20);

    if (count($filearray)) {
        print '<div class="wpeo-gridlayout grid-5 grid-gap-3 grid-margin-2 ecm-photo-list">';

        $j = 0;
        for ($i = (($offset - 1) * $imageNumberPerPage); $i < ($imageNumberPerPage + (($offset - 1) * $imageNumberPerPage)); $i++) {
            if (empty($filearray[$i])) {
                continue;
            }

            $fileName = $filearray[$i]['name'];
            if (image_format_supported($fileName) < 0) {
                continue;
            }
            $nbphoto++;

            $thumbName = nsinfo_get_thumb_name($fileName, $modulepart, $size, $filearray[$i]['path']);
            $path      = DOL_URL_ROOT . '/document.php?modulepart=ecm&attachment=0&file=' . str_replace('/', '%2F', $modulepart . '/medias/thumbs');
            $fullpath  = $path . '/' . urlencode((string) $thumbName) . '&entity=' . $conf->entity;

            print '<div class="center clickable-photo clickable-photo' . $j . '" value="' . $j . '">';
            print '  <figure class="photo-image">';
            if ($thumbName !== -1 && dol_is_file($filearray[$i]['path'] . '/thumbs/' . $thumbName)) {
                print '    <input class="filename" type="hidden" value="' . dol_escape_htmltag($fileName) . '">';
                print '    <img class="photo photo' . $j . '" width="' . $maxWidth . '" height="' . $maxHeight . '" data-src="' . $fullpath . '" loading="lazy">';
            } else {
                print '    <input type="hidden" class="fullname" data-fullname="' . dol_escape_htmltag($filearray[$i]['fullname']) . '">';
                print '    <i class="clicked-photo-preview regenerate-thumbs fas fa-redo"></i>';
                print '    <img class="photo photo' . $j . '" width="' . $maxWidth . '" height="' . $maxHeight . '" data-src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" loading="lazy">';
            }
            print '  </figure>';
            print '  <div class="title">' . dol_escape_htmltag($fileName) . '</div>';
            print '</div>';
            $j++;
        }
        print '</div>';
    } else {
        print '<br>';
        print '<div class="ecm-photo-list">';
        print $langs->trans('EmptyMediaGallery');
        print '</div>';
    }

    if (is_object($user)) {
        $user->nbphotogallery = $nbphoto;
    }
}

/**
 * Affiche les médias liés à un objet
 *
 * @param  string      $modulepart           Nom du sous-module
 * @param  string      $sdir                 Chemin du répertoire
 * @param  int|string  $size                 Taille des médias
 * @param  int|string  $nbmax                Nombre max de médias affichés par page
 * @param  int         $nbbyrow              Nombre d'images par ligne
 * @param  int         $showfilename         Afficher le nom du fichier sous l'image
 * @param  int         $showaction           Affiche l'icône avec les liens d'action
 * @param  int         $maxHeight            Hauteur max du média
 * @param  int         $maxWidth             Largeur max du média
 * @param  int         $nolink 	             Ne pas ajouter de lien href à l'image
 * @param  int         $notitle              Ne pas ajouter de balise title à l'image
 * @param  int         $usesharelink         Utiliser le lien partagé public de l'image (si indisponible, l'image 'nophoto' sera affichée à la place)
 * @param  string      $subdir               Sous-répertoire du fichier
 * @param  object|null $object               Objet lié dont afficher les médias
 * @param  string      $favorite_field       Nom du champ SQL favori de l'objet
 * @param  int         $show_favorite_button Affiche ou masque le bouton favori
 * @param  int         $show_unlink_button   Affiche ou masque le bouton délier
 * @param  int         $use_mini_format      Utiliser le format mini au lieu de small
 * @param  int         $show_only_favorite   Afficher uniquement les médias favoris de l'objet
 * @param  string      $morecss              Ajoute du CSS au lien
 * @param  int         $showdiv              Ajoute une div avec la classe « media-container »
 * @return string      $return               Affiche les médias liés
 */
function nsinfo_show_medias_linked(string $modulepart = 'ecm', string $sdir, $size = 0, $nbmax = 0, int $nbbyrow = 5, int $showfilename = 0, int $showaction = 0, int $maxHeight = 120, int $maxWidth = 160, int $nolink = 0, int $notitle = 0, int $usesharelink = 0, string $subdir = '', object $object = null, string $favorite_field = 'photo', int $show_favorite_button = 1, int $show_unlink_button = 1 , int $use_mini_format = 0, int $show_only_favorite = 0, string $morecss = '', int $showdiv = 1, array $moreParams = []): string
{
    global $conf, $langs, $moduleNameUpperCase;

    $sortfield = 'position_name';
    $sortorder = 'desc';

    //	$dir  = $sdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');
    //	$pdir = $subdir . '/' . (dol_strlen($object->ref) > 0 ? $object->ref . '/' : '');

    $dir  = $sdir . (substr($sdir, -1) == '/' ? '' : '/');
    $pdir = $subdir . (substr($subdir, -1) == '/' ? '' : '/');

    $dirthumb  = $dir . 'thumbs/';
    $pdirthumb = $pdir . 'thumbs/';

    $return  = '<!-- Photo -->' . "\n";
    $nbphoto = 0;

    $filearray = dol_dir_list($dir, 'files', 0,  $moreParams['filter'] ?? '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

    $i = 0;
    if (count($filearray)) {
        if ($sortfield && $sortorder) {
            $filearray = dol_sort_array($filearray, $sortfield, $sortorder);
        }
        $favoriteExists = 0;
        foreach ($filearray as $file) {
            if ($file['name'] == $object->$favorite_field) {
                $favoriteExists = 1;
            }
        }

        if ($show_unlink_button) {
            $confirmationParams = [
                'picto'             => 'fontawesome_fa-unlink_fas_#e05353',
                'color'             => '#e05353',
                'confirmationTitle' => 'ConfirmUnlinkMedia',
                'buttonParams'      => ['No' => 'button-blue marginrightonly confirmation-close', 'Yes' => 'button-red confirmation-delete']
            ];
            require __DIR__ . '/../core/tpl/utils/confirmation_view.tpl.php';
        }

        foreach ($filearray as $file) {
            $photo    = '';
            $fileName = $file['name'];
            $filePath = $file['path'];

            if (($show_only_favorite && ($object->$favorite_field == $fileName || !$favoriteExists)) || !$show_only_favorite) {
                if ($showdiv) {
                    $return .= '<div class="media-container">';
                }

                $return .= '<input hidden class="file-path" value="'. $filePath .'">';
                $return .= '<input hidden class="file-name" value="'. $fileName .'">';
                if (image_format_supported($fileName) >= 0) {
                    $nbphoto++;
                    $photo        = $fileName;
                    $viewfilename = $fileName;

                    if ($size == 1 || $size == 'small') {   // Format vignette
                        // Recherche le nom du fichier vignette
                        if ($use_mini_format) {
                            $photo_vignette = basename(getImageFileNameForSize($dir . $fileName, '_mini'));
                        } else {
                            $photo_vignette = basename(getImageFileNameForSize($dir . $fileName, '_small'));
                        }

                        if ( ! dol_is_file($dirthumb . $photo_vignette)) $photo_vignette = '';

                        // Récupère la taille du fichier original
                        $imgarray = dol_getImageSize($dir . $photo);

                        if ($nbbyrow > 0) {
                            if ($nbphoto == 1) $return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';

                            if ($nbphoto % $nbbyrow == 1) $return .= '<tr class="center valignmiddle" style="border: 1px">';
                            $return                               .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%" class="photo">';
                        } elseif ($nbbyrow < 0) $return .= '<div class="inline-block">';

                        $return .= "\n";

                        $relativefile = preg_replace('/^\//', '', $pdir . $photo);
                        if (empty($nolink)) {
                            $relativefile              = preg_replace("/'/", "\\'", $relativefile);
                            $urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
                            if ($urladvanced) $return .= '<a class="clicked-photo-preview" href="' . $urladvanced . '">';
                            else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
                        }

                        // Afficher l'image (largeur, hauteur=$maxHeight)
                        $alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
                        $alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
                        if ($notitle) $alt = '';
                        if ($usesharelink) {
                            if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
                                $return .= '<!-- Affiche la vignette -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $object->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            } else {
                                $return .= '<!-- Affiche le fichier original -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $object->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            }
                        } else {
                            if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
                                $return .= '<!-- Affiche la vignette -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .'"  src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            } else {
                                $return .= '<!-- Affiche le fichier original -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            }
                        }

                        if (empty($nolink)) $return .= '</a>';
                        $return                     .= "\n";
                        if ($showfilename) $return  .= '<br>' . $viewfilename;
                        if ($showaction) {
                            $return .= '<br>';
                            if ($photo_vignette && (image_format_supported($photo) > 0) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight)) {
                                $return .= '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=addthumb&amp;file=' . urlencode($pdir . $viewfilename) . '">' . img_picto($langs->trans('GenerateThumb'), 'refresh') . '&nbsp;&nbsp;</a>';
                            }
                        }
                        $return .= "\n";

                        if ($nbbyrow > 0) {
                            $return                                 .= '</td>';
                            if (($nbphoto % $nbbyrow) == 0) $return .= '</tr>';
                        } elseif ($nbbyrow < 0) $return .= '</td>';
                    }

                    if (empty($size)) {
                        // Format origine
                        $return .= '<img class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" data-object-id="' . $object->id . '">';
                        if ($showfilename) {
                            $return .= '<br>' . $viewfilename;
                        }
                    }

                    if ($size == 'large' || $size == 'medium') {
                        $relativefile = preg_replace('/^\//', '', $pdir . $photo);
                        if (empty($nolink)) {
                            $urladvanced               = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity=' . $conf->entity);
                            if ($urladvanced) $return .= '<a class="clicked-photo-preview" href="' . $urladvanced . '">';
                            else $return              .= '<a href="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" class="aphoto" target="_blank">';
                        }
                        $widthName  = $moduleNameUpperCase . '_MEDIA_MAX_WIDTH_' . strtoupper($size);
                        $heightName = $moduleNameUpperCase . '_MEDIA_MAX_HEIGHT_' . strtoupper($size);
                        $return .= '<img width="' . $conf->global->$widthName . '" height="' . $conf->global->$heightName . '" class="photo photowithmargin" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdir . $photo) . '" data-object-id="' . $object->id . '">';
                        if ($showfilename) {
                            $return .= '<br>' . $viewfilename;
                        }
                    }
                }

                if ($show_favorite_button) {

                    $favorite = (($object->$favorite_field == '' || $favoriteExists == 0) && $i == 0) ? 'favorite' : ($object->$favorite_field == $photo ? 'favorite' : '');
                    $return .=
                        '<div class="wpeo-button button-square-50 button-blue ' . $object->element . ' media-gallery-favorite ' . $favorite . '" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="' . ($favorite == 'favorite' ? 'fas' : 'far') . ' fa-star button-icon"></i>
						</div>';
                }
                if ($show_unlink_button) {
                    $return .=
                        '<div class="wpeo-button button-square-50 button-grey ' . $object->element . ' media-gallery-unlink" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="fas fa-unlink button-icon"></i>
						</div>';
                }

                // ADDED_FOR_AI
                if (isModEnabled('ai') && !empty($moreParams['useAi']) &&
                    (getDolGlobalString('AI_API_SERVICE') && getDolGlobalString('AI_API_' . dol_strtoupper(getDolGlobalString('AI_API_SERVICE')) . '_KEY') && getDolGlobalString('AI_API_' . dol_strtoupper(getDolGlobalString('AI_API_SERVICE')) . '_URL'))
                ) {
                    $return .=
                        '<div class="wpeo-button button-square-50 button-blue ' . $object->element . ' media-gallery-ai" value="' . $object->id . '">
							<input class="element-linked-id" type="hidden" value="' . ($object->id > 0 ? $object->id : 0) . '">
							<input class="filename" type="hidden" value="' . $photo . '">
							<i class="fas fa-magic button-icon"></i>
						</div>';
                }
                if ($showdiv) {
                    $return .= "</div>\n";
                }

                // On continue ou on arrete de boucler ?
                if ($nbmax && $nbphoto >= $nbmax) break;

                $i++;
            }
        }

        if ($size == 1 || $size == 'small') {
            if ($nbbyrow > 0) {
                // Ferme tableau
                while ($nbphoto % $nbbyrow) {
                    $return .= '<td style="width: ' . ceil(100 / $nbbyrow) . '%">&nbsp;</td>';
                    $nbphoto++;
                }

                if ($nbphoto) $return .= '</table>';
            }
        }
    } else {
        $return .= '<img  width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png" title="' . $langs->trans('NoPhotoYet') . '">';
    }

    if (is_object($object)) {
        $object->nbphoto = $nbphoto;
    }
    return $return;
}