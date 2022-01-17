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

namespace Component\Circle;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Feed;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Nickname;
use Component\Circle\Controller as CircleController;
use Component\Circle\Entity\ActorCircle;
use Component\Circle\Entity\ActorCircleSubscription;
use Component\Circle\Entity\ActorTag;
use Component\Collection\Util\MetaCollectionTrait;
use Component\Tag\Tag;
use Functional as F;
use Symfony\Component\HttpFoundation\Request;

/**
 * Component responsible for handling and representing ActorCircles and ActorTags
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Phablulo <phablulo@gmail.com>
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Circle extends Component
{
    use MetaCollectionTrait;
    public const TAG_CIRCLE_REGEX = '/' . Nickname::BEFORE_MENTIONS . '@#([\pL\pN_\-\.]{1,64})/';
    protected string $slug        = 'circle';
    protected string $plural_slug = 'circles';

    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('actor_circle_view_by_circle_id', '/circle/{circle_id<\d+>}', [CircleController\Circle::class, 'circleById']);
        // View circle members by (tagger id or nickname) and tag
        $r->connect('actor_circle_view_by_circle_tagger_tag', '/circle/actor/{tagger_id<\d+>/{tag<' . Tag::TAG_SLUG_REGEX . '>}}', [CircleController\Circle::class, 'circleByTaggerIdAndTag']);
        $r->connect('actor_circle_view_by_circle_tagger_tag', '/circle/@{nickname<' . Nickname::DISPLAY_FMT . '>}/{tag<' . Tag::TAG_SLUG_REGEX . '>}', [CircleController\Circle::class, 'circleByTaggerNicknameAndTag']);

        // View all circles by actor id or nickname
        $r->connect(
            id: 'actor_circles_view_by_actor_id',
            uri_path: '/actor/{tag<' . Tag::TAG_SLUG_REGEX . '>}/circles',
            target: [CircleController\Circles::class, 'collectionsViewByActorId'],
        );
        $r->connect(
            id: 'actor_circles_view_by_nickname',
            uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/circles',
            target: [CircleController\Circles::class, 'collectionsViewByActorNickname'],
        );

        $r->connect('actor_circle_view_feed_by_circle_id', '/circle/{circle_id<\d+>}/feed', [CircleController\Circles::class, 'feedByCircleId']);
        // View circle feed by (tagger id or nickname) and tag
        $r->connect('actor_circle_view_feed_by_circle_tagger_tag', '/circle/actor/{tagger_id<\d+>/{tag<' . Tag::TAG_SLUG_REGEX . '>}}/feed', [CircleController\Circles::class, 'feedByTaggerIdAndTag']);
        $r->connect('actor_circle_view_feed_by_circle_tagger_tag', '/circle/@{nickname<' . Nickname::DISPLAY_FMT . '>}/{tag<' . Tag::TAG_SLUG_REGEX . '>}/feed', [CircleController\Circles::class, 'feedByTaggerNicknameAndTag']);

        return Event::next;
    }

    public static function cacheKeys(string $tag_single_or_multi): array
    {
        return [
            'actor_single' => "actor-tag-feed-{$tag_single_or_multi}",
            'actor_multi'  => "actor-tags-feed-{$tag_single_or_multi}",
        ];
    }

    public function onPopulateSettingsTabs(Request $request, string $section, array &$tabs): bool
    {
        if ($section === 'profile' && $request->get('_route') === 'settings') {
            $tabs[] = [
                'title'      => 'Self tags',
                'desc'       => 'Add or remove tags on yourself',
                'id'         => 'settings-self-tags',
                'controller' => CircleController\SelfTagsSettings::settingsSelfTags($request, Common::actor(), 'settings-self-tags-details'),
            ];
        }
        return Event::next;
    }

    public function onPostingFillTargetChoices(Request $request, Actor $actor, array &$targets): bool
    {
        $circles = $actor->getCircles();
        foreach ($circles as $circle) {
            $tag                = $circle->getTag();
            $targets["#{$tag}"] = $tag;
        }
        return Event::next;
    }

    // Meta Collection -------------------------------------------------------------------

    private function getActorIdFromVars(array $vars): int
    {
        $id = $vars['request']->get('id', null);
        if ($id) {
            return (int) $id;
        }
        $nick = $vars['request']->get('nickname');
        $user = LocalUser::getByNickname($nick);
        return $user->getId();
    }

    public static function createCircle(Actor|int $tagger_id, string $tag): int
    {
        $tagger_id = \is_int($tagger_id) ? $tagger_id : $tagger_id->getId();
        $circle    = ActorCircle::create([
            'tagger'      => $tagger_id,
            'tag'         => $tag,
            'description' => null, // TODO
            'private'     => false, // TODO
        ]);
        DB::persist($circle);

        Cache::delete(Actor::cacheKeys($tagger_id)['circles']);

        return $circle->getId();
    }

    protected function createCollection(Actor $owner, array $vars, string $name)
    {
        $this->createCircle($owner, $name);
        DB::persist(ActorTag::create([
            'tagger' => $owner->getId(),
            'tagged' => self::getActorIdFromVars($vars),
            'tag'    => $name,
        ]));
    }

    protected function removeItem(Actor $owner, array $vars, $items, array $collections)
    {
        $tagger_id                     = $owner->getId();
        $tagged_id                     = $this->getActorIdFromVars($vars);
        $circles_to_remove_tagged_from = DB::findBy(ActorCircle::class, ['id' => $items]);
        foreach ($circles_to_remove_tagged_from as $circle) {
            DB::removeBy(ActorCircleSubscription::class, ['actor_id' => $tagged_id, 'circle_id' => $circle->getId()]);
        }
        $tags = F\map($circles_to_remove_tagged_from, fn ($x) => $x->getTag());
        foreach ($tags as $tag) {
            DB::removeBy(ActorTag::class, ['tagger' => $tagger_id, 'tagged' => $tagged_id, 'tag' => $tag]);
        }
        Cache::delete(Actor::cacheKeys($tagger_id)['circles']);
    }

    protected function addItem(Actor $owner, array $vars, $items, array $collections)
    {
        $tagger_id                = $owner->getId();
        $tagged_id                = $this->getActorIdFromVars($vars);
        $circles_to_add_tagged_to = DB::findBy(ActorCircle::class, ['id' => $items]);
        foreach ($circles_to_add_tagged_to as $circle) {
            DB::persist(ActorCircleSubscription::create(['actor_id' => $tagged_id, 'circle_id' => $circle->getId()]));
        }
        $tags = F\map($circles_to_add_tagged_to, fn ($x) => $x->getTag());
        foreach ($tags as $tag) {
            DB::persist(ActorTag::create(['tagger' => $tagger_id, 'tagged' => $tagged_id, 'tag' => $tag]));
        }
        Cache::delete(Actor::cacheKeys($tagger_id)['circles']);
    }

    /**
     * @see MetaCollectionPlugin->shouldAddToRightPanel
     */
    protected function shouldAddToRightPanel(Actor $user, $vars, Request $request): bool
    {
        return \in_array($vars['path'], ['actor_view_nickname', 'actor_view_id']);
    }

    protected function getCollectionsBy(Actor $owner, ?array $vars = null, bool $ids_only = false): array
    {
        $tagged_id = !\is_null($vars) ? $this->getActorIdFromVars($vars) : null;
        $circles   = \is_null($tagged_id) ? $owner->getCircles() : F\select($owner->getCircles(), function ($x) use ($tagged_id) {
            foreach ($x->getActorTags() as $at) {
                if ($at->getTagged() === $tagged_id) {
                    return true;
                }
            }
            return false;
        });
        return $ids_only ? array_map(fn ($x) => $x->getId(), $circles) : $circles;
    }

    public function onCreateDefaultFeeds(int $actor_id, LocalUser $user, int &$ordering)
    {
        DB::persist(Feed::create([
            'actor_id' => $actor_id,
            'url'      => Router::url($route = 'actor_circles_view_by_nickname', ['nickname' => $user->getNickname()]),
            'route'    => $route,
            'title'    => _m('Circles'),
            'ordering' => $ordering++,
        ]));
        return Event::next;
    }
}
