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
 * \file    lib/pagination.lib.php
 * \ingroup nsinfo
 * \brief   Fichiers de bibliothèque avec les fonctions communes à la pagination NSINFO
 */

/**
 * Charge le tableau des pages à afficher
 *
 * @param  float      $pagesCounter Nombre de pages
 * @param  array|null $pageArray    Tableau de toutes les pages disponibles
 * @param  int|null   $offset       Selected page
 * @return array      $page_array   Tableau des numéros de pages
 */
function nsinfo_load_pagination(float $pagesCounter, ?array $pageArray, ?int $offset): array
{
    if (empty($pageArray)) {
        $offset      = $offset ?: 1;
        $pageArray[] = '<i class="fas fa-arrow-left"></i>';

        if ($pagesCounter > 4) {
            if ($offset > 2) {
                $pageArray[] = '...';
            }

            if ($offset == 1) {
                $pageArray[] = $offset;
                $pageArray[] = $offset + 1;
                $pageArray[] = $offset + 2;
                $pageArray[] = $offset + 3;
            } elseif ($offset > 1 && $offset < $pagesCounter) {
                if ($offset == $pagesCounter - 1) {
                    $pageArray[] = $offset - 2;
                }
                $pageArray[] = $offset - 1;
                $pageArray[] = $offset;
                $pageArray[] = $offset + 1;
                if ($offset == 2) {
                    $pageArray[] = $offset + 2;
                }
            } elseif ($offset == $pagesCounter) {
                $pageArray[] = $offset - 3;
                $pageArray[] = $offset - 2;
                $pageArray[] = $offset - 1;
                $pageArray[] = $offset;
            }

            if ($pagesCounter > 3 && $offset < $pagesCounter - 1) {
                $pageArray[] = '...';
            }
        } else {
            for ($i = 1; $i <= $pagesCounter; $i++) {
                $pageArray[] = $i;
            }
        }

        $pageArray[] = '<i class="fas fa-arrow-right"></i>';
    }

    return $pageArray;
}

/**
 * Affiche les pages à partir du tableau de pages chargé
 *
 * @param  float      $pagesCounter     Nombre de pages
 * @param  array|null $pageArray        Tableau de toutes les pages disponibles
 * @param  int|null   $offset           Selected page
 * @param  string     $containerToRefresh Id du conteneur que le clic de pagination doit rafraîchir
 * @return string     $return           Contenu HTML des pages
 */
function nsinfo_show_pagination(float $pagesCounter, ?array $pageArray, ?int $offset, string $containerToRefresh = 'media_gallery'): string
{
    $offset = $offset ?: 1;
    $return = '<ul class="wpeo-pagination">';
    $return .= '<input hidden id="pagesCounter" value="' . ($pagesCounter) . '">';
    $return .= '<input hidden id="containerToRefresh" value="' . $containerToRefresh . '">';
    $return .= '<input hidden id="currentOffset" value="' . ($offset ?: 1) . '">';

    foreach ($pageArray as $pageNumber) {
        $return .= '<li class="pagination-element ' . ($pageNumber == $offset ? 'pagination-current' : ($pageNumber == 1 && !$offset ? 'pagination-current' : '')) . '">';
        if ($pageNumber == '...') {
            $return .= '<span>' . $pageNumber . '</span>';
        } elseif ($pageNumber == '<i class="fas fa-arrow-left"></i>') {
            $return .= '<a class="select-page arrow arrow-left" value="' . max(($offset - 1), 1) . '"><i class="fas fa-arrow-left"></i></a>';
        } elseif ($pageNumber == '<i class="fas fa-arrow-right"></i>') {
            $return .= '<a class="select-page arrow arrow-right" value="' . min(($offset + 1), $pagesCounter) . '"><i class="fas fa-arrow-right"></i></a>';
        } else {
            $return .= '<a class="select-page" value="' . $pageNumber . '">' . $pageNumber . '</a>';
        }

        $return .= '</li>';
    }

    $return .= '</ul>';
    return $return;
}
