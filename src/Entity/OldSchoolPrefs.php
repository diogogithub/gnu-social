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
 * Entity for Separate table for storing UI preferences
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @deprecated
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OldSchoolPrefs
{
    // {{{ Autocode

    private int $user_id;
    private ?bool $stream_mode_only;
    private ?bool $conversation_tree;
    private ?bool $stream_nicknames;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setStreamModeOnly(?bool $stream_mode_only): self
    {
        $this->stream_mode_only = $stream_mode_only;
        return $this;
    }

    public function getStreamModeOnly(): ?bool
    {
        return $this->stream_mode_only;
    }

    public function setConversationTree(?bool $conversation_tree): self
    {
        $this->conversation_tree = $conversation_tree;
        return $this;
    }

    public function getConversationTree(): ?bool
    {
        return $this->conversation_tree;
    }

    public function setStreamNicknames(?bool $stream_nicknames): self
    {
        $this->stream_nicknames = $stream_nicknames;
        return $this;
    }

    public function getStreamNicknames(): ?bool
    {
        return $this->stream_nicknames;
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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'old_school_prefs',
            'fields' => [
                'user_id'          => ['type' => 'int', 'not null' => true, 'description' => 'user who has the preference'],
                'stream_mode_only' => ['type'  => 'bool',
                    'default'                  => true,
                    'description'              => 'No conversation streams', ],
                'conversation_tree' => ['type' => 'bool',
                    'default'                  => true,
                    'description'              => 'Hierarchical tree view for conversations', ],
                'stream_nicknames' => ['type'  => 'bool',
                    'default'                  => true,
                    'description'              => 'Show nicknames for authors and addressees in streams', ],
                'created'  => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['user_id'],
            'foreign keys' => [
                'old_school_prefs_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }
}