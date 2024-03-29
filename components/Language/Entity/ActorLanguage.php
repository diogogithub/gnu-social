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

namespace Component\Language\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Common;
use Functional as F;

/**
 * Entity for actor languages
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActorLanguage extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private int $language_id;
    private int $ordering;

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setLanguageId(int $language_id): self
    {
        $this->language_id = $language_id;
        return $this;
    }

    public function getLanguageId(): int
    {
        return $this->language_id;
    }

    public function setOrdering(int $ordering): self
    {
        $this->ordering = $ordering;
        return $this;
    }

    public function getOrdering(): int
    {
        return $this->ordering;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function cacheKeys(LocalUser|Actor|int $actor, ?Actor $context = null): array
    {
        $actor_id = \is_int($actor) ? $actor : $actor->getId();
        return [
            'related-ids' => "actor-{$actor_id}-lang-related-ids",
            'actor-langs' => "actor-{$actor_id}-langs" . (!\is_null($context) ? "-cxt-{$context->getId()}" : ''),
        ];
    }

    public static function normalizeOrdering(LocalUser|Actor $actor)
    {
        $langs = DB::dql('select l.locale, al.ordering, l.id from language l join actor_language al with l.id = al.language_id where al.actor_id = :id order by al.ordering ASC', ['id' => $actor->getId()]);
        usort($langs, fn ($l, $r) => [$l['ordering'], $l['locale']] <=> [$r['ordering'], $r['locale']]);
        foreach ($langs as $order => $l) {
            $actor_lang = DB::getReference('actor_language', ['actor_id' => $actor->getId(), 'language_id' => $l['id']]);
            $actor_lang->setOrdering($order + 1);
        }
    }

    /**
     * @return Language[]
     */
    public static function getActorLanguages(LocalUser|Actor $actor, ?Actor $context = null): array
    {
        $id = $context?->getId() ?? $actor->getId();
        return Cache::getList(
            self::cacheKeys($actor, context: $context)['actor-langs'],
            fn () => DB::dql(
                'select l from actor_language al join language l with al.language_id = l.id where al.actor_id = :id order by al.ordering ASC',
                ['id' => $id],
            ),
        ) ?: [Language::getByLocale(Common::config('site', 'language'))];
    }

    public static function getActorRelatedLanguagesIds(Actor $actor): array
    {
        return Cache::getList(
            self::cacheKeys($actor)['related-ids'],
            function () use ($actor) {
                return F\map(
                    F\flat_map(
                        self::getActorLanguages($actor),
                        function ($language) {
                            if (str_contains($language->getLocale(), '_')) {
                                // Actor selected a language with a country, so don't attempt to provide alternatives
                                return $language;
                            } else {
                                // Actor selected a language without a country, so find all variants of the language
                                return DB::dql('select l from language l where l.locale like :locale', ['locale' => $language->getLocale() . '%']);
                            }
                        },
                    ),
                    fn ($l) => $l->getId(),
                );
            },
        );
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'actor_language',
            'description' => 'join table where one actor can have many languages',
            'fields'      => [
                'actor_id'    => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to many', 'description' => 'the actor this language entry refers to'],
                'language_id' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Language.id', 'multiplicity' => 'many to many', 'description' => 'the language this entry refers to'],
                'ordering'    => ['type' => 'int', 'not null' => true, 'description' => 'the order in which a user\'s language options should be displayed'],
            ],
            'primary key' => ['actor_id', 'language_id'],
            'indexes'     => [
                'actor_idx' => ['actor_id'],
            ],
        ];
    }
}
