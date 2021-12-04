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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util\Model;

use ActivityPhp\Type\AbstractObject;
use App\Core\Event;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Language;
use App\Entity\Note as GSNote;
use App\Util\Formatting;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Util\Model;

/**
 * This class handles translation between JSON and GSNotes
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Note extends Model
{

    /**
     * Create an Entity from an ActivityStreams 2.0 JSON string
     * This will persist a new GSNote
     *
     * @param string|AbstractObject $json
     * @param array $options
     * @return GSNote
     * @throws Exception
     */
    public static function fromJson(string|AbstractObject $json, array $options = []): GSNote
    {
        $source = $options['source'];
        $actor_uri = $options['actor_uri'];
        $actor_id = $options['actor_id'];
        $type_note = is_string($json) ? self::jsonToType($json) : $json;

        if (is_null($actor_uri) || $actor_uri !== $type_note->get('attributedTo')) {
            $actor_id = ActivityPub::getActorByUri($type_note->get('attributedTo'))->getId();
        }
        $map = [
            'is_local' => false,
            'created' => new DateTime($type_note->get('published') ?? 'now'),
            'content' => $type_note->get('content') ?? null,
            'content_type' => 'text/html',
            'language_id' => $type_note->get('contentLang') ?? null,
            'url' => $type_note->get('url') ?? $type_note->get('id'),
            'actor_id' => $actor_id,
            'modified' => new DateTime(),
            'source' => $source,
        ];
        if ($map['content'] !== null) {
            $mentions = [];
            Event::handle('RenderNoteContent', [
                $map['content'],
                $map['content_type'],
                &$map['rendered'],
                Actor::getById($actor_id),
                $map['language_id'],
                &$mentions,
            ]);
        }

        $obj = new GSNote();

        if (!is_null($map['language_id'])) {
            $map['language_id'] = Language::getFromLocale($map['language_id'])->getId();
        } else {
            $map['language_id'] = null;
        }

        foreach ($map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $obj->{$set}($val);
        }

        Event::handle('ActivityPubNewNote', [&$obj]);
        return $obj;
    }

    /**
     * Get a JSON
     *
     * @param mixed $object
     * @param int|null $options
     * @return string
     * @throws Exception
     */
    public static function toJson(mixed $object, ?int $options = null): string
    {
        if ($object::class !== 'App\Entity\Note') {
            throw new InvalidArgumentException('First argument type is Note');
        }

        $attr = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => Router::url('note_view', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            'published' => $object->getCreated()->format(DateTimeInterface::RFC3339),
            'attributedTo' => $object->getActor()->getUri(Router::ABSOLUTE_URL),
            'to' => ['https://www.w3.org/ns/activitystreams#Public'], // TODO: implement proper scope address
            'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
            'content' => $object->getRendered(),
            //'tag' => $tags
        ];

        $type = self::jsonToType($attr);
        Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
        return $type->toJson($options);
    }
}