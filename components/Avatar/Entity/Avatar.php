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

namespace Component\Avatar\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Router\Router;
use App\Entity\Attachment;
use App\Util\Common;
use DateTimeInterface;

/**
 * Entity for user's avatar
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Avatar extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private int $attachment_id;
    private ?string $title;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setAttachmentId(int $attachment_id): self
    {
        $this->attachment_id = $attachment_id;
        return $this;
    }

    public function getAttachmentId(): int
    {
        return $this->attachment_id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    private ?Attachment $attachment = null;

    public function getUrl(string $size = 'full', int $type = Router::ABSOLUTE_PATH): string
    {
        $actor_id = $this->getActorId();
        return Cache::get("avatar-url-{$actor_id}-{$size}-{$type}", fn () => Router::url('avatar_actor', ['actor_id' => $actor_id, 'size' => $size], $type));
    }

    public function getAttachment(): Attachment
    {
        $this->attachment ??= DB::findOneBy('attachment', ['id' => $this->attachment_id]);
        return $this->attachment;
    }

    public static function getFilePathStatic(string $filename): string
    {
        return Common::config('avatar', 'dir') . $filename;
    }

    public function getPath(): string
    {
        return Common::config('avatar', 'dir') . $this->getAttachment()->getFileName();
    }

    /**
     * Delete this avatar and kill corresponding attachment
     */
    public function delete(): bool
    {
        DB::remove($this);
        $attachment = $this->getAttachment();
        $attachment->kill();
        DB::flush();
        return true;
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'avatar',
            'fields' => [
                'actor_id'      => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to actor table'],
                'attachment_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Attachment.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to attachment table'],
                'title'         => ['type' => 'varchar',   'length' => 191, 'description' => 'file name of resource when available'],
                'created'       => ['type' => 'datetime', 'not null' => true, 'description' => 'date this record was created', 'default' => 'CURRENT_TIMESTAMP'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key' => ['actor_id'],
            'indexes'     => [
                'avatar_attachment_id_idx' => ['attachment_id'],
            ],
        ];
    }
}
