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
    private ?string $colour_foreground_light;
    private ?string $colour_background_hard_light;
    private ?string $colour_background_card_light;
    private ?string $colour_border_light;
    private ?string $colour_accent_light;
    private ?string $colour_shadow_light;
    private ?string $colour_foreground_dark;
    private ?string $colour_background_hard_dark;
    private ?string $colour_background_card_dark;
    private ?string $colour_border_dark;
    private ?string $colour_accent_dark;
    private ?string $colour_shadow_dark;
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

    public function setColourForegroundLight(?string $colour_foreground_light): self
    {
        $this->colour_foreground_light = $colour_foreground_light;
        return $this;
    }

    public function getColourForegroundLight(): ?string
    {
        return $this->colour_foreground_light;
    }

    public function setColourBackgroundHardLight(?string $colour_background_hard_light): self
    {
        $this->colour_background_hard_light = $colour_background_hard_light;
        return $this;
    }

    public function getColourBackgroundHardLight(): ?string
    {
        return $this->colour_background_hard_light;
    }

    public function setColourBackgroundCardLight(?string $colour_background_card_light): self
    {
        $this->colour_background_card_light = $colour_background_card_light;
        return $this;
    }

    public function getColourBackgroundCardLight(): ?string
    {
        return $this->colour_background_card_light;
    }

    public function setColourBorderLight(?string $colour_border_light): self
    {
        $this->colour_border_light = $colour_border_light;
        return $this;
    }

    public function getColourBorderLight(): ?string
    {
        return $this->colour_border_light;
    }

    public function setColourAccentLight(?string $colour_accent_light): self
    {
        $this->colour_accent_light = $colour_accent_light;
        return $this;
    }

    public function getColourAccentLight(): ?string
    {
        return $this->colour_accent_light;
    }

    public function setColourShadowLight(?string $colour_shadow_light): self
    {
        $this->colour_shadow_light = $colour_shadow_light;
        return $this;
    }

    public function getColourShadowLight(): ?string
    {
        return $this->colour_shadow_light;
    }

    public function setColourForegroundDark(?string $colour_foreground_dark): self
    {
        $this->colour_foreground_dark = $colour_foreground_dark;
        return $this;
    }

    public function getColourForegroundDark(): ?string
    {
        return $this->colour_foreground_dark;
    }

    public function setColourBackgroundHardDark(?string $colour_background_hard_dark): self
    {
        $this->colour_background_hard_dark = $colour_background_hard_dark;
        return $this;
    }

    public function getColourBackgroundHardDark(): ?string
    {
        return $this->colour_background_hard_dark;
    }

    public function setColourBackgroundCardDark(?string $colour_background_card_dark): self
    {
        $this->colour_background_card_dark = $colour_background_card_dark;
        return $this;
    }

    public function getColourBackgroundCardDark(): ?string
    {
        return $this->colour_background_card_dark;
    }

    public function setColourBorderDark(?string $colour_border_dark): self
    {
        $this->colour_border_dark = $colour_border_dark;
        return $this;
    }

    public function getColourBorderDark(): ?string
    {
        return $this->colour_border_dark;
    }

    public function setColourAccentDark(?string $colour_accent_dark): self
    {
        $this->colour_accent_dark = $colour_accent_dark;
        return $this;
    }

    public function getColourAccentDark(): ?string
    {
        return $this->colour_accent_dark;
    }

    public function setColourShadowDark(?string $colour_shadow_dark): self
    {
        $this->colour_shadow_dark = $colour_shadow_dark;
        return $this;
    }

    public function getColourShadowDark(): ?string
    {
        return $this->colour_shadow_dark;
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
                'actor_id'                     => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to actor table'],
                'colour_foreground_light'      => ['type' => 'text',      'description' => 'color hex code'],
                'colour_background_hard_light' => ['type' => 'text',      'description' => 'color hex code'],
                'colour_background_card_light' => ['type' => 'text',      'description' => 'color hex code'],
                'colour_border_light'          => ['type' => 'text',      'description' => 'color hex code'],
                'colour_accent_light'          => ['type' => 'text',      'description' => 'color hex code'],
                'colour_shadow_light'          => ['type' => 'text',      'description' => 'color hex code'],
                'colour_foreground_dark'       => ['type' => 'text',      'description' => 'color hex code'],
                'colour_background_hard_dark'  => ['type' => 'text',      'description' => 'color hex code'],
                'colour_background_card_dark'  => ['type' => 'text',      'description' => 'color hex code'],
                'colour_border_dark'           => ['type' => 'text',      'description' => 'color hex code'],
                'colour_accent_dark'           => ['type' => 'text',      'description' => 'color hex code'],
                'colour_shadow_dark'           => ['type' => 'text',      'description' => 'color hex code'],
                'created'                      => ['type' => 'datetime',  'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
                'modified'                     => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key' => ['actor_id'],
        ];
    }
}
