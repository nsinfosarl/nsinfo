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
 * \brief   Library files with common functions for NSINFO Medias
 */

include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';



/**
 * Show medias linked to an object
 *
 * @param  string      $modulepart           Submodule name
 * @param  string      $sdir                 Directory path
 * @param  int|string  $size                 Medias size
 * @param  int|string  $nbmax                Max number of medias shown per page
 * @param  int         $nbbyrow              Number of images per row
 * @param  int         $showfilename         Show filename under image
 * @param  int         $showaction           Show icon with action links
 * @param  int         $maxHeight            Media max height
 * @param  int         $maxWidth             Media max width
 * @param  int         $nolink 	             Do not add href link to image
 * @param  int         $notitle              Do not add title tag on image
 * @param  int         $usesharelink         Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
 * @param  string      $subdir               Subdir for file
 * @param  object|null $object               Object linked to show medias of
 * @param  string      $favorite_field       Name of favorite sql field of object
 * @param  int         $show_favorite_button Show or hide favorite button
 * @param  int         $show_unlink_button   Show or hide unlink button
 * @param  int         $use_mini_format      Use media mini format instead of small
 * @param  int         $show_only_favorite   Show only object favorite media
 * @param  string      $morecss              Add more CSS on link
 * @param  int         $showdiv              Add div with "media-container" class
 * @return string      $return               Show medias linked
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
                        // Find name of thumb file
                        if ($use_mini_format) {
                            $photo_vignette = basename(getImageFileNameForSize($dir . $fileName, '_mini'));
                        } else {
                            $photo_vignette = basename(getImageFileNameForSize($dir . $fileName, '_small'));
                        }

                        if ( ! dol_is_file($dirthumb . $photo_vignette)) $photo_vignette = '';

                        // Get filesize of original file
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

                        // Show image (width height=$maxHeight)
                        $alt               = $langs->transnoentitiesnoconv('File') . ': ' . $relativefile;
                        $alt              .= ' - ' . $langs->transnoentitiesnoconv('Size') . ': ' . $imgarray['width'] . 'x' . $imgarray['height'];
                        if ($notitle) $alt = '';
                        if ($usesharelink) {
                            if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
                                $return .= '<!-- Show thumb file -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" height="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=' . $modulepart . '&entity=' . $object->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            } else {
                                $return .= '<!-- Show original file -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .' photowithmargin" src="' . DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=' . $modulepart . '&entity=' . $object->entity . '&file=' . urlencode($pdir . $photo) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            }
                        } else {
                            if (empty($maxHeight) || $photo_vignette && $imgarray['height'] > $maxHeight) {
                                $return .= '<!-- Show thumb file -->';
                                $return .= '<img width="' . $maxWidth . '" height="' . $maxHeight . '" class="photo '. $morecss .'"  src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $modulepart . '&entity=' . $conf->entity . '&file=' . urlencode($pdirthumb . $photo_vignette) . '" title="' . dol_escape_htmltag($alt) . '" data-object-id="' . $object->id . '">';
                            } else {
                                $return .= '<!-- Show original file -->';
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
                    $confirmationParams = [
                        'picto'             => 'fontawesome_fa-unlink_fas_#e05353',
                        'color'             => '#e05353',
                        'confirmationTitle' => 'ConfirmUnlinkMedia',
                        'buttonParams'      => ['No' => 'button-blue marginrightonly confirmation-close', 'Yes' => 'button-red confirmation-delete']
                    ];
                    require __DIR__ . '/../core/tpl/utils/confirmation_view.tpl.php';
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