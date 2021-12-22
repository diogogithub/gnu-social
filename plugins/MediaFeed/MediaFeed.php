<?php

declare(strict_types=1);

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}
/**
 * Media Feed Plugin for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\MediaFeed;

use App\Core\Event;
use App\Entity\Note;
use Functional as F;
use App\Entity\Actor;
use App\Util\Formatting;
use App\Core\Modules\Plugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\Definition\Exception\Exception;

class MediaFeed extends Plugin
{
    public function onFilterNoteList(?Actor $actor, array &$notes, Request $request): bool
    {
        if ($request->get('filter-type') === 'media') {
            $notes = F\select($notes, fn (Note $n) => \count($n->getAttachments()) > 0);
        }
        return Event::next;
    }
    /**
     * Draw the media feed navigation.
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function onBeforeFeed(Request $request, &$res): bool
    {
        $isMediaActive = $request->get('filter-type') === 'media';
        // we need two urls: one with filter-type=media and without it.
        $query    = strpos($request->getRequestUri(), '?');
        $mediaURL = $request->getRequestUri() . ($query !== false ? '&' : '?') . 'filter-type=media';
        $allURL   = $request->getPathInfo();
        if ($query !== false) {
            $params  = explode('&', substr($request->getRequestUri(), $query + 1));
            $params  = array_filter($params, fn ($s) => $s !== 'filter-type=media');
            $params  = implode('&', $params);
            if ($params) {
                $allURL .= '?' . $params;
            }
        }

        $res[] = Formatting::twigRenderFile('mediaFeeed/tabs.html.twig', [
            'main' => [
                'active' => !$isMediaActive,
                'url' => $isMediaActive ? $allURL : '',
            ],
            'media' => [
                'active' => $isMediaActive,
                'url' => $isMediaActive ? '' : $mediaURL,
            ]
        ]);
        return Event::next;
    }
    /**
     * Output our dedicated stylesheet
     *
     * @param array $styles stylesheets path
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onEndShowStyles(array &$styles, string $route): bool
    {
        $styles[] = 'plugins/MediaFeed/assets/css/mediaFeed.css';
        return Event::next;
    }
}
