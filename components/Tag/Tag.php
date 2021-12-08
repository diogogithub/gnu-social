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
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Language;
use App\Entity\Note;
use App\Entity\NoteTag;
use App\Util\Exception\ClientException;
use App\Util\Formatting;
use App\Util\HTML;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;

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
        $r->connect('single_note_tag', '/note-tag/{canon<' . self::TAG_SLUG_REGEX . '>}', [Controller\Tag::class, 'single_note_tag']);
        $r->connect('multi_note_tags', '/note-tags/{canons<(' . self::TAG_SLUG_REGEX . ',)+' . self::TAG_SLUG_REGEX . '>}', [Controller\Tag::class, 'multi_note_tags']);
        $r->connect('single_actor_tag', '/actor-tag/{canon<' . self::TAG_SLUG_REGEX . '>}', [Controller\Tag::class, 'single_actor_tag']);
        $r->connect('multi_actor_tags', '/actor-tags/{canons<(' . self::TAG_SLUG_REGEX . ',)+' . self::TAG_SLUG_REGEX . '>}', [Controller\Tag::class, 'multi_actor_tags']);
        return Event::next;
    }

    /**
     * Process note by extracting any tags present
     */
    public function onProcessNoteContent(Note $note, string $content, string $content_type, array $extra_args): bool
    {
        $matched_tags   = [];
        $processed_tags = false;
        preg_match_all(self::TAG_REGEX, $content, $matched_tags, \PREG_SET_ORDER);
        foreach ($matched_tags as $match) {
            $tag           = self::ensureValid($match[2]);
            $canonical_tag = self::canonicalTag($tag, Language::getById($note->getLanguageId())->getLocale());
            DB::persist(NoteTag::create([
                'tag'           => $tag,
                'canonical'     => $canonical_tag,
                'note_id'       => $note->getId(),
                'use_canonical' => $extra_args['tag_use_canonical'],
            ]));
            Cache::pushList("tag-{$canonical_tag}", $note);
            $processed_tags = true;
            foreach (self::cacheKeys($canonical_tag) as $key) {
                Cache::delete($key);
            }
        }
        if ($processed_tags) {
            DB::flush();
        }
        return Event::next;
    }

    public function onRenderPlainTextNoteContent(string &$text, ?string $language = null): bool
    {
        $text = preg_replace_callback(self::TAG_REGEX, fn ($m) => $m[1] . self::tagLink($m[2], $language), $text);
        return Event::next;
    }

    public static function cacheKeys(string $canon_single_or_multi): array
    {
        return [
            'note_single'  => "note-tag-feed-{$canon_single_or_multi}",
            'note_multi'   => "note-tags-feed-{$canon_single_or_multi}",
            'actor_single' => "actor-tag-feed-{$canon_single_or_multi}",
            'actor_multi'  => "actor-tags-feed-{$canon_single_or_multi}",
        ];
    }

    private static function tagLink(string $tag, ?string $language): string
    {
        $tag       = self::ensureLength($tag);
        $canonical = self::canonicalTag($tag, $language);
        $url       = Router::url('single_note_tag', !\is_null($language) ? ['canon' => $canonical, 'lang' => $language, 'tag' => $tag] : ['canon' => $canonical, 'tag' => $tag]);
        return HTML::html(['a' => ['attrs' => ['href' => $url, 'title' => $tag, 'rel' => 'tag'], $tag]], options: ['indent' => false]);
    }

    public static function ensureValid(string $tag)
    {
        return self::ensureLength(str_replace('#', '', $tag));
    }

    public static function ensureLength(string $tag): string
    {
        return mb_substr($tag, 0, self::MAX_TAG_LENGTH);
    }

    /**
     * Convert a tag to it's canonical representation, by splitting it
     * into words, stemming it in the given language (if enabled) and
     * sluggifying it (turning it into an ASCII representation)
     */
    public static function canonicalTag(string $tag, ?string $language): string
    {
        $result = '';
        foreach (Formatting::splitWords(str_replace('#', '', $tag)) as $word) {
            $temp_res = null;
            if (\is_null($language) || Event::handle('StemWord', [$language, $word, &$temp_res]) !== Event::stop) {
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
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, &$note_expr, &$actor_expr): bool
    {
        $search_term       = str_contains($term, ':#') ? explode(':', $term)[1] : $term;
        $canon_search_term = self::canonicalTag($search_term, $language);
        $temp_note_expr    = $eb->eq('note_tag.canonical', $canon_search_term);
        $temp_actor_expr   = $eb->eq('actor_tag.canonical', $canon_search_term);
        if (Formatting::startsWith($term, ['note', 'tag'])) {
            $note_expr = $temp_note_expr;
        } elseif (Formatting::startsWith($term, ['people', 'actor'])) {
            $actor_expr = $temp_actor_expr;
        } else {
            $note_expr  = $temp_note_expr;
            $actor_expr = $temp_actor_expr;
            return Event::next;
        }
        return Event::stop;
    }

    public function onSearchQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin('App\Entity\NoteTag', 'note_tag', Expr\Join::WITH, 'note_tag.note_id = note.id');
        $actor_qb->leftJoin('App\Entity\ActorTag', 'actor_tag', Expr\Join::WITH, 'actor_tag.tagger = actor.id');
        return Event::next;
    }

    public function onPostingAddFormEntries(Request $request, Actor $actor, array &$form_params)
    {
        $form_params[] = ['tag_use_canonical', CheckboxType::class, ['required' => false, 'data' => true, 'label' => _m('Make note tags canonical'), 'help' => _m('Canonical tags will be treated as a version of an existing tag with the same root/stem (e.g. \'#great_tag\' will be considered as a version of \'#great\', if it already exists)')]];
        return Event::next;
    }

    public function onPostingHandleForm(Request $request, Actor $actor, array $data, array &$extra_args)
    {
        if (!isset($data['tag_use_canonical'])) {
            throw new ClientException;
        }
        $extra_args['tag_use_canonical'] = $data['tag_use_canonical'];
        return Event::next;
    }
}
