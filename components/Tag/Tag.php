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

namespace Component\Tag;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Core\Router\Router;
use App\Entity\Language;
use App\Entity\Note;
use App\Entity\NoteTag;
use App\Util\Formatting;
use App\Util\HTML;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * Component responsible for extracting tags from posted notes, as well as normalizing them
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Tag extends Component
{
    public const MAX_TAG_LENGTH = 64;
    public const TAG_REGEX      = '/(^|\\s)(#[\\pL\\pN_\\-\\.]{1,64})/u'; // Brion Vibber 2011-02-23 v2:classes/Notice.php:367 function saveTags
    public const TAG_SLUG_REGEX = '[A-Za-z0-9]{1,64}';

    public function onAddRoute($r): bool
    {
        $r->connect('single_tag', '/tag/{tag<' . self::TAG_SLUG_REGEX . '>}', [Controller\Tag::class, 'single_tag']);
        $r->connect('multiple_tags', '/tags/{tags<(' . self::TAG_SLUG_REGEX . ',)+' . self::TAG_SLUG_REGEX . '>}', [Controller\Tag::class, 'multi_tags']);
        return Event::next;
    }

    /**
     * Process note by extracting any tags present
     */
    public function onProcessNoteContent(Note $note, string $content): bool
    {
        $matched_tags   = [];
        $processed_tags = false;
        preg_match_all(self::TAG_REGEX, $content, $matched_tags, \PREG_SET_ORDER);
        foreach ($matched_tags as $match) {
            $tag           = self::ensureLength($match[2]);
            $canonical_tag = self::canonicalTag($tag, Language::getFromId($note->getLanguageId())->getLocale());
            DB::persist(NoteTag::create(['tag' => $tag, 'canonical' => $canonical_tag, 'note_id' => $note->getId()]));
            Cache::pushList("tag-{$canonical_tag}", $note);
            $processed_tags = true;
        }
        if ($processed_tags) {
            DB::flush();
        }
        return Event::next;
    }

    public function onRenderPlainTextNoteContent(string &$text, ?string $language = null): bool
    {
        if (!is_null($language)) {
            $text = preg_replace_callback(self::TAG_REGEX, fn ($m) => $m[1] . self::tagLink($m[2], $language), $text);
        }
        return Event::next;
    }

    private static function tagLink(string $tag, string $language): string
    {
        $tag       = self::ensureLength($tag);
        $canonical = self::canonicalTag($tag, $language);
        $url       = Router::url('tag', ['tag' => $canonical, 'lang' => $language]);
        return HTML::html(['a' => ['attrs' => ['href' => $url, 'title' => $tag, 'rel' => 'tag'], $tag]], options: ['indent' => false]);
    }

    public static function ensureLength(string $tag): string
    {
        return mb_substr($tag, 0, self::MAX_TAG_LENGTH);
    }

    public static function canonicalTag(string $tag, string $language): string
    {
        $result = '';
        foreach (Formatting::splitWords(str_replace('#', '', $tag)) as $word) {
            $temp_res = null;
            if (Event::handle('StemWord', [$language, $word, &$temp_res]) !== Event::stop) {
                $temp_res = $word;
            }
            $result .= Formatting::slugify($temp_res);
        }
        return self::ensureLength($result);
    }

    /**
     * Populate $note_expr with an expression to match a tag, if the term looks like a tag
     *
     * $term /^(note|tag|people|actor)/ means we want to match only either a note or an actor
     */
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, &$note_expr, &$actor_expr): bool
    {
        $search_term     = str_contains($term, ':#') ? explode(':', $term)[1] : $term;
        $temp_note_expr  = $eb->eq('note_tag.tag', $search_term);
        $temp_actor_expr = $eb->eq('actor_tag.tag', $search_term);
        if (Formatting::startsWith($term, ['note', 'tag'])) {
            $note_expr = $temp_note_expr;
        } else {
            if (Formatting::startsWith($term, ['people', 'actor'])) {
                $actor_expr = $temp_actor_expr;
            } else {
                $note_expr  = $temp_note_expr;
                $actor_expr = $temp_actor_expr;
                return Event::next;
            }
        }
        return Event::stop;
    }

    public function onSeachQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->join('App\Entity\NoteTag', 'note_tag', Expr\Join::WITH, 'note_tag.note_id = note.id');
        $actor_qb->join('App\Entity\ActorTag', 'actor_tag', Expr\Join::WITH, 'actor_tag.tagger = actor.id');
        return Event::next;
    }
}
