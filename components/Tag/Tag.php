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
use App\Entity\ActorCircle;
use App\Entity\ActorTag;
use App\Entity\Note;
use App\Entity\NoteTag;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Formatting;
use App\Util\Functional as GSF;
use App\Util\HTML;
use App\Util\Nickname;
use Component\Language\Entity\Language;
use Component\Tag\Controller as C;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Functional as F;
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
    public const MAX_TAG_LENGTH   = 64;
    public const TAG_REGEX        = '/(^|\\s)(#[\\pL\\pN_\\-]{1,64})/u'; // Brion Vibber 2011-02-23 v2:classes/Notice.php:367 function saveTags
    public const TAG_CIRCLE_REGEX = '/' . Nickname::BEFORE_MENTIONS . '@#([\pL\pN_\-\.]{1,64})/';
    public const TAG_SLUG_REGEX   = '[A-Za-z0-9]{1,64}';

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
        if ($extra_args['TagProcessed'] ?? false) {
            return Event::next;
        }
        // XXX: We remove <span> because when content is in html the tag comes as #<span>hashtag</span>
        $content      = str_replace('<span>', '', $content);
        $matched_tags = [];
        preg_match_all(self::TAG_REGEX, $content, $matched_tags, \PREG_SET_ORDER);
        $matched_tags = array_unique(F\map($matched_tags, fn ($m) => $m[2]));
        foreach ($matched_tags as $match) {
            $tag           = self::ensureValid($match);
            $canonical_tag = self::canonicalTag($tag, \is_null($lang_id = $note->getLanguageId()) ? null : Language::getById($lang_id)->getLocale());
            DB::persist(NoteTag::create([
                'tag'           => $tag,
                'canonical'     => $canonical_tag,
                'note_id'       => $note->getId(),
                'use_canonical' => $extra_args['tag_use_canonical'] ?? false,
                'language_id'   => $lang_id,
            ]));
            Cache::pushList("tag-{$canonical_tag}", $note);
            foreach (self::cacheKeys($canonical_tag) as $key) {
                Cache::delete($key);
            }
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
        $tag = self::ensureLength(Formatting::removePrefix($tag, '#'));
        if (preg_match(self::TAG_REGEX, '#' . $tag)) {
            return $tag;
        } else {
            throw new ClientException(_m('Invalid tag given: {tag}', ['{tag}' => $tag]));
        }
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
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, ?Actor $actor, &$note_expr, &$actor_expr)
    {
        [$search_type, $search_term] = explode(':', $term);
        if (str_starts_with($search_term, '#')) {
            $search_term       = self::ensureValid($search_term);
            $canon_search_term = self::canonicalTag($search_term, $language);
            $temp_note_expr    = $eb->eq('note_tag.canonical', $canon_search_term);
            $temp_actor_expr   = $eb->eq('actor_tag.canonical', $canon_search_term);
            if (Formatting::startsWith($term, ['note:', 'tag:', 'people:'])) {
                $note_expr = $temp_note_expr;
            } elseif (Formatting::startsWith($term, ['people:', 'actor:'])) {
                $actor_expr = $temp_actor_expr;
            } elseif (Formatting::startsWith($term, GSF::cartesianProduct([['people', 'actor'], ['circle', 'list'], [':']], separator: ['-', '_']))) {
                $null_tagger_expr = $eb->isNull('actor_circle.tagger');
                $tagger_expr      = \is_null($actor_expr) ? $null_tagger_expr : $eb->orX($null_tagger_expr, $eb->eq('actor_circle.tagger', $actor->getId()));
                $tags             = array_unique([$search_term, $canon_search_term]);
                $tag_expr         = \count($tags) === 1 ? $eb->eq('actor_circle.tag', $tags[0]) : $eb->in('actor_circle.tag', $tags);
                $search_expr      = $eb->andX(
                    $tagger_expr,
                    $tag_expr,
                );
                $note_expr  = $search_expr;
                $actor_expr = $search_expr;
            } else {
                $note_expr  = $temp_note_expr;
                $actor_expr = $temp_actor_expr;
                return Event::next;
            }
        }
        return Event::stop;
    }

    public function onSearchQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin(NoteTag::class, 'note_tag', Expr\Join::WITH, 'note_tag.note_id = note.id')
            ->leftJoin(ActorCircle::class, 'actor_circle', Expr\Join::WITH, 'note_actor.id = actor_circle.tagged');
        $actor_qb->leftJoin(ActorTag::class, 'actor_tag', Expr\Join::WITH, 'actor_tag.tagger = actor.id')
            ->leftJoin(ActorCircle::class, 'actor_circle', Expr\Join::WITH, 'actor.id = actor_circle.tagged');
        return Event::next;
    }

    public function onPostingAddFormEntries(Request $request, Actor $actor, array &$form_params)
    {
        $form_params[] = ['tag_use_canonical', CheckboxType::class, ['required' => false, 'data' => true, 'label' => _m('Make note tags canonical'), 'help' => _m('Canonical tags will be treated as a version of an existing tag with the same root/stem (e.g. \'#great_tag\' will be considered as a version of \'#great\', if it already exists)')]];
        return Event::next;
    }

    public function onAddExtraArgsToNoteContent(Request $request, Actor $actor, array $data, array &$extra_args)
    {
        if (!isset($data['tag_use_canonical'])) {
            throw new ClientException;
        }
        $extra_args['tag_use_canonical'] = $data['tag_use_canonical'];
        return Event::next;
    }

    public function onPopulateSettingsTabs(Request $request, string $section, array &$tabs)
    {
        if ($section === 'profile' && $request->get('_route') === 'settings') {
            $tabs[] = [
                'title'      => 'Self tags',
                'desc'       => 'Add or remove tags on yourself',
                'id'         => 'settings-self-tags',
                'controller' => C\Tag::settingsSelfTags($request, Common::actor(), 'settings-self-tags-details'),
            ];
        }
        return Event::next;
    }

    public function onPostingFillTargetChoices(Request $request, Actor $actor, array &$targets)
    {
        $actor_id = $actor->getId();
        $tags     = Cache::get(
            "actor-circle-{$actor_id}",
            fn () => DB::dql('select c.tag from actor_circle c where c.tagger = :tagger', ['tagger' => $actor_id]),
        );
        foreach ($tags as $t) {
            $t           = '#' . $t['tag'];
            $targets[$t] = $t;
        }
        return Event::next;
    }
}
