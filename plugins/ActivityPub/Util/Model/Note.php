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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util\Model;

use ActivityPhp\Type;
use ActivityPhp\Type\AbstractObject;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\HTTPClient;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Language;
use App\Entity\Note as GSNote;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NoSuchActorException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Component\Attachment\Entity\ActorToAttachment;
use Component\Attachment\Entity\AttachmentToNote;
use Component\Conversation\Conversation;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Entity\ActivitypubObject;
use Plugin\ActivityPub\Util\Model;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
     * @throws ClientException
     * @throws ClientExceptionInterface
     * @throws DuplicateFoundException
     * @throws NoSuchActorException
     * @throws RedirectionExceptionInterface
     * @throws ServerException
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function fromJson(string|AbstractObject $json, array $options = []): GSNote
    {
        $handleInReplyTo = function (AbstractObject|string $type_note): ?int {
            try {
                $parent_note = is_null($type_note->get('inReplyTo')) ? null : ActivityPub::getObjectByUri($type_note->get('inReplyTo'), try_online: true);
                if ($parent_note instanceof \App\Entity\Note) {
                    return $parent_note->getId();
                } elseif ($parent_note instanceof Type\AbstractObject && $parent_note->get('type') === 'Note') {
                    return self::fromJson($parent_note)->getId();
                } else {
                    return null;
                }
            } catch (Exception $e) {
                Log::debug('ActivityStreams:Model:Note-> An error occurred retrieving parent note.', [$e]);
                // Sadly we won't be able to have this note inside the correct conversation for now.
                // TODO: Create an entity that registers notes falsely without parent so, when the parent is retrieved,
                // we can update the child with the correct parent.
                return null;
            }
        };

        $source    = $options['source'] ?? 'ActivityPub';
        $type_note = \is_string($json) ? self::jsonToType($json) : $json;
        $actor     = null;
        $actor_id  = null;
        if ($json instanceof AbstractObject
            && \array_key_exists('test_authority', $options)
            && $options['test_authority']
            && \array_key_exists('actor_uri', $options)
        ) {
            $actor_uri = $options['actor_uri'];
            if ($actor_uri !== $type_note->get('attributedTo')) {
                if (parse_url($actor_uri)['host'] !== parse_url($type_note->get('attributedTo'))['host']) {
                    throw new Exception('You don\'t seem to have enough authority to create this note.');
                }
            } else {
                $actor    = $options['actor']    ?? null;
                $actor_id = $options['actor_id'] ?? $actor?->getId();
            }
        }

        if (\is_null($actor_id)) {
            $actor    = ActivityPub::getActorByUri($type_note->get('attributedTo'));
            $actor_id = $actor->getId();
        }
        $map = [
            'is_local'     => false,
            'created'      => new DateTime($type_note->get('published') ?? 'now'),
            'content'      => $type_note->get('content') ?? null,
            'rendered'     => null,
            'content_type' => 'text/html',
            'language_id'  => $type_note->get('contentLang') ?? null,
            'url'          => $type_note->get('url') ?? $type_note->get('id'),
            'actor_id'     => $actor_id,
            'reply_to'     => $reply_to = $handleInReplyTo($type_note),
            'modified'     => new DateTime(),
            'source'       => $source,
        ];
        if ($map['content'] !== null) {
            $mentions = [];
            Event::handle('RenderNoteContent', [
                $map['content'],
                $map['content_type'],
                &$map['rendered'],
                $actor,
                $map['language_id'],
                &$mentions,
            ]);
        }

        $obj = new GSNote();

        if (!\is_null($map['language_id'])) {
            $map['language_id'] = Language::getByLocale($map['language_id'])->getId();
        } else {
            $map['language_id'] = null;
        }

        foreach ($map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $obj->{$set}($val);
        }

        // Attachments
        $processed_attachments = [];
        foreach ($type_note->get('attachment') as $attachment) {
            if ($attachment->get('type') === 'Document') {
                // Retrieve media
                $get_response = HTTPClient::get($attachment->get('url'));
                $media        = $get_response->getContent();
                unset($get_response);
                // Ignore empty files
                if (!empty($media)) {
                    // Create an attachment for this
                    $temp_file = new TemporaryFile();
                    $temp_file->write($media);
                    $filesize      = $temp_file->getSize();
                    $max_file_size = Common::getUploadLimit();
                    if ($max_file_size < $filesize) {
                        throw new ClientException(_m('No file may be larger than {quota} bytes and the file you sent was {size} bytes. '
                            . 'Try to upload a smaller version.', ['quota' => $max_file_size, 'size' => $filesize], ));
                    }
                    Event::handle('EnforceUserFileQuota', [$filesize, $actor_id]);

                    $processed_attachments[] = [GSFile::storeFileAsAttachment($temp_file), $attachment->get('name')];
                }
            }
        }

        DB::persist($obj);

        // Assign conversation to this note
        Conversation::assignLocalConversation($obj, $reply_to);

        // Need file and note ids for the next step
        Event::handle('ProcessNoteContent', [$obj, $obj->getContent(), $obj->getContentType(), $process_note_content_extra_args = []]);

        if ($processed_attachments !== []) {
            foreach ($processed_attachments as [$a, $fname]) {
                if (DB::count('actor_to_attachment', $args = ['attachment_id' => $a->getId(), 'actor_id' => $actor_id]) === 0) {
                    DB::persist(ActorToAttachment::create($args));
                }
                DB::persist(AttachmentToNote::create(['attachment_id' => $a->getId(), 'note_id' => $obj->getId(), 'title' => $fname]));
            }
        }

        $map = [
            'object_uri'  => $type_note->get('id'),
            'object_type' => 'note',
            'object_id'   => $obj->getId(),
            'created'     => new DateTime($type_note->get('published') ?? 'now'),
            'modified'    => new DateTime(),
        ];
        $ap_obj = new ActivitypubObject();
        foreach ($map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $ap_obj->{$set}($val);
        }
        DB::persist($ap_obj);

        return $obj;
    }

    /**
     * Get a JSON
     *
     * @throws Exception
     */
    public static function toJson(mixed $object, ?int $options = null): string
    {
        if ($object::class !== 'App\Entity\Note') {
            throw new InvalidArgumentException('First argument type is Note');
        }

        $attr = [
            '@context'      => 'https://www.w3.org/ns/activitystreams',
            'type'          => 'Note',
            'id'            => $object->getUrl(),
            'published'     => $object->getCreated()->format(DateTimeInterface::RFC3339),
            'attributedTo'  => $object->getActor()->getUri(Router::ABSOLUTE_URL),
            'to'            => ['https://www.w3.org/ns/activitystreams#Public'], // TODO: implement proper scope address
            'cc'            => ['https://www.w3.org/ns/activitystreams#Public'],
            'content'       => $object->getRendered(),
            'attachment'    => [],
            'tag'           => [],
            'conversation'  => $object->getConversationUri(),
            'directMessage' => false, // TODO: implement proper scope address
        ];

        // Mentions
        foreach ($object->getNotificationTargets() as $mention) {
            $attr['tag'][] = [
                'type' => 'Mention',
                'href' => ($href = $mention->getUri()),
                'name' => '@' . $mention->getNickname() . '@' . parse_url($href, \PHP_URL_HOST),
            ];
            $attr['cc'][] = $href;
        }

        // Attachments
        foreach ($object->getAttachments() as $attachment) {
            $attr['attachment'][] = [
                'type'      => 'Document',
                'mediaType' => $attachment->getMimetype(),
                'url'       => $attachment->getUrl(Router::ABSOLUTE_URL),
                'name'      => AttachmentToNote::getByPK(['attachment_id' => $attachment->getId(), 'note_id' => $object->getId()])->getTitle(),
                'width'     => $attachment->getWidth(),
                'height'    => $attachment->getHeight(),
            ];
        }

        $type = self::jsonToType($attr);
        Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
        return $type->toJson($options);
    }
}
