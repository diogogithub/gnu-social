<?php

declare(strict_types = 1);

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

namespace Component\Group\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;

use App\Entity\Actor;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Nickname;
use DateTimeInterface;

/**
 * Entity for local groups
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
class LocalGroup extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $group_id;
    private ?string $nickname = null;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function getGroupId(): int
    {
        return $this->group_id;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = \is_null($nickname) ? null : mb_substr($nickname, 0, 64);
        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
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

    public function getActor()
    {
        return DB::find('actor', ['id' => $this->group_id]);
    }

    public static function getByNickname(string $nickname): ?self
    {
        $res = DB::findBy(self::class, ['nickname' => $nickname]);
        return $res === [] ? null : $res[0];
    }

    public static function getActorByNickname(string $nickname): ?Actor
    {
        $res = DB::findBy(Actor::class, ['nickname' => $nickname, 'type' => Actor::GROUP]);
        return $res === [] ? null : $res[0];
    }

    /**
     * Checks if desired nickname is allowed, and in case it is, it sets Actor's nickname cache to newly set nickname
     *
     * @param string $nickname Desired NEW nickname (do not use in local user creation)
     *
     * @throws NicknameEmptyException
     * @throws NicknameException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     *
     * @return $this
     */
    public function setNicknameSanitizedAndCached(string $nickname): self
    {
        $nickname = Nickname::normalize($nickname, check_already_used: true, which: Nickname::CHECK_LOCAL_GROUP, check_is_allowed: true);
        $this->setNickname($nickname);
        $this->getActor()->setNickname($nickname);
        /// XXX: cache?
        return $this;
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'local_group',
            'description' => 'Record for a user group on the local site, with some additional info not in user_group',
            'fields'      => [
                'group_id' => ['type' => 'int',      'foreign key' => true, 'target' => 'Group.id', 'multiplicity' => 'one to one', 'name' => 'local_group_group_id_fkey', 'not null' => true, 'description' => 'group represented'],
                'nickname' => ['type' => 'varchar',  'length' => 64,        'description' => 'group represented'],
                'created'  => ['type' => 'datetime', 'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['group_id'],
            'unique keys' => [
                'local_group_nickname_key' => ['nickname'],
            ],
        ];
    }
}
