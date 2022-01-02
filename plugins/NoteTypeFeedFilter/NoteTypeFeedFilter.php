<?php

declare(strict_types = 1);

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
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\NoteTypeFeedFilter;

use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Plugin;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Exception\BugFoundException;
use App\Util\Exception\ClientException;
use App\Util\Formatting;
use App\Util\Functional as GSF;
use Functional as F;
use Symfony\Component\HttpFoundation\Request;

// TODO: Migrate this to query filters
class NoteTypeFeedFilter extends Plugin
{
    public const ALLOWED_TYPES = ['media', 'link', 'text', 'tag'];

    private function unknownType(string $type): ClientException
    {
        return new ClientException(_m('Unknown note type requested ({type})', ['{type}' => $type]));
    }

    private function normalizeTypesList(array $types, bool $add_missing = true): array
    {
        if (empty($types)) {
            return self::ALLOWED_TYPES;
        } else {
            $result = [];
            foreach (self::ALLOWED_TYPES as $allowed_type) {
                foreach ($types as $type) {
                    if ($type === 'all') {
                        return self::ALLOWED_TYPES;
                    } elseif (mb_detect_encoding($type, 'ASCII', strict: true) === false || empty($type)) {
                        throw $this->unknownType($type);
                    } elseif (\in_array(
                        $allowed_type,
                        GSF::cartesianProduct([
                            ['', '!'],
                            [$type, mb_substr($type, 1), mb_substr($type, 0, -1)], // The original, without the first or without the last character
                        ]),
                    )) {
                        $result[] = ($type[0] === '!' ? '!' : '') . $allowed_type;
                        continue 2;
                    }
                } // else
                if ($add_missing) {
                    $result[] = '!' . $allowed_type;
                }
            }
            return $result;
        }
    }

    public function onFilterNoteList(?Actor $actor, array &$notes, Request $request): bool
    {
        $types = $this->normalizeTypesList(\is_null($request->get('note-types')) ? [] : explode(',', $request->get('note-types')));
        $notes = F\select(
            $notes,
            function (Note $note) use ($types) {
                $include = false; // TODO Would like to express this as a reduce of some sort...
                foreach ($types as $type) {
                    $is_negate = $type[0] === '!';
                    $type = Formatting::removePrefix($type, '!');
                    switch ($type) {
                    case 'text':
                        $ret = !\is_null($note->getContent());
                        break;
                    case 'media':
                        $ret = !empty($note->getAttachments());
                        break;
                    case 'link':
                        $ret = !empty($note->getLinks());
                        break;
                    case 'tag':
                        $ret = !empty($note->getTags());
                        break;
                    default:
                        throw new BugFoundException("Unkown note type requested {$type}", previous: $this->unknownType($type));
                    }
                    if ($is_negate && $ret) {
                        return false;
                    }
                    $include = $include || $ret;
                }
                return $include;
            },
        );
        return Event::next;
    }

    /**
     * Draw the media feed navigation.
     */
    public function onAddFeedActions(Request $request, &$res): bool
    {
        $qs = [];
        parse_str($request->getQueryString(), $qs);
        if (\array_key_exists('p', $qs) && \is_string($qs['p'])) {
            unset($qs['p']);
        }
        $types  = $this->normalizeTypesList(\is_null($request->get('note-types')) ? [] : explode(',', $request->get('note-types')), add_missing: false);
        $ftypes = array_flip($types);

        $tabs = [
            'all' => [
                'active' => empty($types) || $types === self::ALLOWED_TYPES,
                'url'    => '?' . http_build_query(['note-types' => implode(',', self::ALLOWED_TYPES)], '', '&', \PHP_QUERY_RFC3986),
                'icon'   => 'All',
            ],
        ];

        foreach (self::ALLOWED_TYPES as $allowed_type) {
            $active               = \array_key_exists($allowed_type, $ftypes);
            $new_types            = $this->normalizeTypesList([($active ? '!' : '') . $allowed_type, ...$types], add_missing: false);
            $new_qs               = $qs;
            $new_qs['note-types'] = implode(',', $new_types);
            $tabs[$allowed_type]  = [
                'active' => $active,
                'url'    => '?' . http_build_query($new_qs, '', '&', \PHP_QUERY_RFC3986),
                'icon'   => $allowed_type,
            ];
        }

        $res[] = Formatting::twigRenderFile('NoteTypeFeedFilter/tabs.html.twig', ['tabs' => $tabs]);
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
        $styles[] = 'plugins/NoteTypeFeedFilter/assets/css/noteTypeFeedFilter.css';
        return Event::next;
    }
}
