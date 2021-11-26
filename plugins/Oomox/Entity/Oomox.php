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

namespace Plugin\Oomox\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * For storing theme colour settings
 *
 * @package  GNUsocial
 * @category Oomox
 *
 * @author    Eliseu Amaro  <mail@eliseuama.ro>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Oomox extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private ?string $color;
    private ?string $background;
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

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setBackground(string $background): self
    {
        $this->background = $background;
        return $this;
    }

    public function getBackground(): ?string
    {
        return $this->background;
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
    public static function schemaDef(): array
    {
        return [
            'name'   => 'oomox',
            'fields' => [
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to actor table'],
                'colour_foreground'         => ['type' => 'text',      'description' => 'color hex code'],
                'colour_background_hard'    => ['type' => 'text',      'description' => 'color hex code'],
                'colour_background_card'    => ['type' => 'text',      'description' => 'color hex code'],
                'colour_border'             => ['type' => 'text',      'description' => 'color hex code'],
                'colour_accent'             => ['type' => 'text',      'description' => 'color hex code'],
                'colour_shadow'             => ['type' => 'text',      'description' => 'color hex code'],
                'created'    => ['type' => 'datetime',  'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
                'modified'   => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key' => ['actor_id'],
        ];
    }
}
