<?php

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

namespace App\Entity;

use DateTimeInterface;

/**
 * Entity for List of profiles
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
class ProfileList
{
    // {{{ Autocode

    private int $id;
    private int $tagger;
    private string $tag;
    private ?string $description;
    private ?bool $private;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;
    private ?string $uri;
    private ?string $mainpage;
    private ?int $tagged_count;
    private ?int $subscriber_count;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTagger(int $tagger): self
    {
        $this->tagger = $tagger;
        return $this;
    }

    public function getTagger(): int
    {
        return $this->tagger;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPrivate(?bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    public function getPrivate(): ?bool
    {
        return $this->private;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
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

    public function setMainpage(?string $mainpage): self
    {
        $this->mainpage = $mainpage;
        return $this;
    }

    public function getMainpage(): ?string
    {
        return $this->mainpage;
    }

    public function setTaggedCount(?int $tagged_count): self
    {
        $this->tagged_count = $tagged_count;
        return $this;
    }

    public function getTaggedCount(): ?int
    {
        return $this->tagged_count;
    }

    public function setSubscriberCount(?int $subscriber_count): self
    {
        $this->subscriber_count = $subscriber_count;
        return $this;
    }

    public function getSubscriberCount(): ?int
    {
        return $this->subscriber_count;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'profile_list',
            'fields' => [
                'id'               => ['type' => 'int', 'not null' => true, 'description' => 'unique identifier'],
                'tagger'           => ['type' => 'int', 'not null' => true, 'description' => 'user making the tag'],
                'tag'              => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'people tag'],
                'description'      => ['type' => 'text', 'description' => 'description of the people tag'],
                'private'          => ['type' => 'bool', 'default' => false, 'description' => 'is this tag private'],
                'created'          => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date the tag was added'],
                'modified'         => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date the tag was modified'],
                'uri'              => ['type' => 'varchar', 'length' => 191, 'description' => 'universal identifier'],
                'mainpage'         => ['type' => 'varchar', 'length' => 191, 'description' => 'page to link to'],
                'tagged_count'     => ['type' => 'int', 'default' => 0, 'description' => 'number of people tagged with this tag by this user'],
                'subscriber_count' => ['type' => 'int', 'default' => 0, 'description' => 'number of subscribers to this tag'],
            ],
            'primary key' => ['tagger', 'tag'],
            'unique keys' => [
                'profile_list_id_key' => ['id'],
            ],
            'foreign keys' => [
                'profile_list_tagger_fkey' => ['profile', ['tagger' => 'id']],
            ],
            'indexes' => [
                'profile_list_modified_idx'         => ['modified'],
                'profile_list_tag_idx'              => ['tag'],
                'profile_list_tagger_tag_idx'       => ['tagger', 'tag'],
                'profile_list_tagged_count_idx'     => ['tagged_count'],
                'profile_list_subscriber_count_idx' => ['subscriber_count'],
            ],
        ];
    }
}
