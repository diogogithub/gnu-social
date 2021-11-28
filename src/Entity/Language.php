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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use function App\Core\I18n\_m;
use App\Util\Common;
use DateTimeInterface;
use Functional as F;

/**
 * Entity for languages
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Language extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private string $locale;
    private string $long_display;
    private string $short_display;
    private DateTimeInterface $created;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLongDisplay(string $long_display): self
    {
        $this->long_display = $long_display;
        return $this;
    }

    public function getLongDisplay(): string
    {
        return $this->long_display;
    }

    public function setShortDisplay(string $short_display): self
    {
        $this->short_display = $short_display;
        return $this;
    }

    public function getShortDisplay(): string
    {
        return $this->short_display;
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
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function getFromId(int $id): self
    {
        return Cache::getHashMapKey(
            map_key: 'languages-id',
            key: (string) $id,
            calculate_map: fn () => F\reindex(DB::dql('select l from language l'), fn (self $l) => (string) $l->getId()),
        );
    }

    public static function getFromLocale(string $locale): self
    {
        return Cache::getHashMapKey(
            'languages',
            $locale,
            calculate_map: fn () => F\reindex(DB::dql('select l from language l'), fn (self $l) => $l->getLocale()),
        );
    }

    public static function getLanguageChoices(): array
    {
        $langs = Cache::getHashMap(
            'languages',
            fn () => F\reindex(DB::dql('select l from language l'), fn (self $l) => $l->getLocale()),
        );

        return array_merge(...F\map(array_values($langs), fn ($l) => $l->toChoiceFormat()));
    }

    public function toChoiceFormat(): array
    {
        return [_m($this->getLongDisplay()) => $this->getLocale()];
    }

    /**
     * Get all the available languages as well as the languages $actor
     * prefers and are appropriate for posting in/to $context_actor
     */
    public static function getSortedLanguageChoices(Actor $actor, ?Actor $context_actor, ?bool $use_short_display): array
    {
        $language_choices           = self::getLanguageChoices();
        $preferred_language_choices = $actor->getPreferredLanguageChoices($context_actor);
        ksort($language_choices);
        if ($use_short_display ?? Common::config('posting', 'use_short_language_display')) {
            $key    = array_key_first($preferred_language_choices);
            $locale = $preferred_language_choices[$key];
            unset($preferred_language_choices[$key], $language_choices[$key]);
            $short_display                              = self::getFromLocale($locale)->getShortDisplay();
            $preferred_language_choices[$short_display] = trim($locale);
            $language_choices[$short_display]           = trim($locale);
        }
        return [$language_choices, $preferred_language_choices];
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'language',
            'description' => 'all known languages',
            'fields'      => [
                'id'            => ['type' => 'serial',  'not null' => true, 'description' => 'unique identifier'],
                'locale'        => ['type' => 'varchar', 'length' => 64, 'description' => 'The locale identifier for the language of a note. 2-leter-iso-language-code_4-leter-script-code_2-leter-iso-country-code, but kept longer in case we get a different format'],
                'long_display'  => ['type' => 'varchar', 'length' => 64, 'description' => 'The long display string for the language, in english (translated later)'],
                'short_display' => ['type' => 'varchar', 'length' => 12,  'description' => 'The short display string for the language (used for the first option)'],
                'created'       => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'language_locale_uniq' => ['locale'],
            ],
            'indexes' => [
                'locale_idx' => ['locale'],
            ],
        ];
    }
}
