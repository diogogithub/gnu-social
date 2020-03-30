<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for notices
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Notice
{
    // {{{ Autocode

    private int $id;
    private int $profile_id;
    private ?string $uri;
    private ?string $content;
    private ?string $rendered;
    private ?string $url;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;
    private ?int $reply_to;
    private ?int $is_local;
    private ?string $source;
    private ?int $conversation;
    private ?int $repeat_of;
    private ?string $object_type;
    private ?string $verb;
    private ?int $scope;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function setProfileId(int $profile_id): self
    {
        $this->profile_id = $profile_id;
        return $this;
    }
    public function getProfileId(): int
    {
        return $this->profile_id;
    }

    public function setUri(?string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }
    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }
    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setRendered(?string $rendered): self
    {
        $this->rendered = $rendered;
        return $this;
    }
    public function getRendered(): ?string
    {
        return $this->rendered;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    public function setReplyTo(?int $reply_to): self
    {
        $this->reply_to = $reply_to;
        return $this;
    }
    public function getReplyTo(): ?int
    {
        return $this->reply_to;
    }

    public function setIsLocal(?int $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }
    public function getIsLocal(): ?int
    {
        return $this->is_local;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }
    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setConversation(?int $conversation): self
    {
        $this->conversation = $conversation;
        return $this;
    }
    public function getConversation(): ?int
    {
        return $this->conversation;
    }

    public function setRepeatOf(?int $repeat_of): self
    {
        $this->repeat_of = $repeat_of;
        return $this;
    }
    public function getRepeatOf(): ?int
    {
        return $this->repeat_of;
    }

    public function setObjectType(?string $object_type): self
    {
        $this->object_type = $object_type;
        return $this;
    }
    public function getObjectType(): ?string
    {
        return $this->object_type;
    }

    public function setVerb(?string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }
    public function getVerb(): ?string
    {
        return $this->verb;
    }

    public function setScope(?int $scope): self
    {
        $this->scope = $scope;
        return $this;
    }
    public function getScope(): ?int
    {
        return $this->scope;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        $def = [
            'name'   => 'notice',
            'fields' => [
                'id'           => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'profile_id'   => ['type' => 'int', 'not null' => true, 'description' => 'who made the update'],
                'uri'          => ['type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier, usually a tag URI'],
                'content'      => ['type' => 'text', 'description' => 'update content', 'collate' => 'utf8mb4_general_ci'],
                'rendered'     => ['type' => 'text', 'description' => 'HTML version of the content'],
                'url'          => ['type' => 'varchar', 'length' => 191, 'description' => 'URL of any attachment (image, video, bookmark, whatever)'],
                'created'      => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
                'reply_to'     => ['type' => 'int', 'description' => 'notice replied to (usually a guess)'],
                'is_local'     => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'notice was generated by a user'],
                'source'       => ['type' => 'varchar', 'length' => 32, 'description' => 'source of comment, like "web", "im", or "clientname"'],
                'conversation' => ['type' => 'int', 'description' => 'the local numerical conversation id'],
                'repeat_of'    => ['type' => 'int', 'description' => 'notice this is a repeat of'],
                'object_type'  => ['type' => 'varchar', 'length' => 191, 'description' => 'URI representing activity streams object type', 'default' => null],
                'verb'         => ['type' => 'varchar', 'length' => 191, 'description' => 'URI representing activity streams verb', 'default' => 'http://activitystrea.ms/schema/1.0/post'],
                'scope'        => ['type' => 'int',
                    'description'         => 'bit map for distribution scope; 0 = everywhere; 1 = this server only; 2 = addressees; 4 = groups; 8 = followers; 16 = messages; null = default', ],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'notice_uri_key' => ['uri'],
            ],
            'foreign keys' => [
                'notice_profile_id_fkey'   => ['profile', ['profile_id' => 'id']],
                'notice_reply_to_fkey'     => ['notice', ['reply_to' => 'id']],
                'notice_conversation_fkey' => ['conversation', ['conversation' => 'id']], // note... used to refer to notice.id
                'notice_repeat_of_fkey'    => ['notice', ['repeat_of' => 'id']], // @fixme: what about repeats of deleted notices?
            ],
            'indexes' => [
                'notice_created_id_is_local_idx'         => ['created', 'id', 'is_local'],
                'notice_profile_id_idx'                  => ['profile_id', 'created', 'id'],
                'notice_is_local_created_profile_id_idx' => ['is_local', 'created', 'profile_id'],
                'notice_repeat_of_created_id_idx'        => ['repeat_of', 'created', 'id'],
                'notice_conversation_created_id_idx'     => ['conversation', 'created', 'id'],
                'notice_object_type_idx'                 => ['object_type'],
                'notice_verb_idx'                        => ['verb'],
                'notice_profile_id_verb_idx'             => ['profile_id', 'verb'],
                'notice_url_idx'                         => ['url'],   // Qvitter wants this
                'notice_replyto_idx'                     => ['reply_to'],
            ],
        ];

        // TODO
        // if (common_config('search', 'type') == 'fulltext') {
        //     $def['fulltext indexes'] = ['content' => ['content']];
        // }

        return $def;
    }
}
