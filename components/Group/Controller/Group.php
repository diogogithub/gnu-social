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

namespace Component\Group\Controller;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\UserRoles;
use App\Entity as E;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use App\Util\Form\ActorForms;
use App\Util\Nickname;
use Component\Collection\Util\ActorControllerTrait;
use Component\Collection\Util\Controller\FeedController;
use Component\Group\Entity\GroupMember;
use Component\Group\Entity\LocalGroup;
use Component\Subscription\Entity\ActorSubscription;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Group extends FeedController
{
    use ActorControllerTrait;
    public function groupViewId(Request $request, int $id)
    {
        return $this->handleActorById(
            $id,
            fn ($actor) => [
                '_template' => 'group/view.html.twig',
                'actor'     => $actor,
            ],
        );
    }

    /**
     * View a group feed and give the option of creating it if it doesn't exist
     */
    public function groupViewNickname(Request $request, string $nickname)
    {
        Nickname::validate($nickname, which: Nickname::CHECK_LOCAL_GROUP); // throws
        $group          = LocalGroup::getActorByNickname($nickname);
        $actor          = Common::actor();
        $subscribe_form = null;

        if (\is_null($group)) {
            if (!\is_null($actor)) {
                $create_form = Form::create([
                    ['create', SubmitType::class, ['label' => _m('Create this group')]],
                ]);

                $create_form->handleRequest($request);
                if ($create_form->isSubmitted() && $create_form->isValid()) {
                    Log::info(
                        _m(
                            'Actor id:{actor_id} nick:{actor_nick} created the group {nickname}',
                            ['{actor_id}' => $actor->getId(), 'actor_nick' => $actor->getNickname(), 'nickname' => $nickname],
                        ),
                    );

                    DB::persist($group = E\Actor::create([
                        'nickname' => $nickname,
                        'type'     => E\Actor::GROUP,
                        'is_local' => true,
                        'roles'    => UserRoles::BOT,
                    ]));
                    DB::persist(LocalGroup::create([
                        'group_id' => $group->getId(),
                        'nickname' => $nickname,
                    ]));
                    DB::persist(ActorSubscription::create([
                        'subscriber' => $group->getId(),
                        'subscribed' => $group->getId(),
                    ]));
                    DB::persist(GroupMember::create([
                        'group_id' => $group->getId(),
                        'actor_id' => $actor->getId(),
                        'is_admin' => true,
                    ]));
                    DB::flush();
                    Cache::delete(E\Actor::cacheKeys($actor->getId())['subscriber']);
                    Cache::delete(E\Actor::cacheKeys($actor->getId())['subscribed']);
                    throw new RedirectException();
                }

                return [
                    '_template'   => 'group/view.html.twig',
                    'nickname'    => $nickname,
                    'create_form' => $create_form->createView(),
                ];
            }
        } else {
            if (!\is_null($actor)
                && \is_null(Cache::get(
                    ActorSubscription::cacheKeys($actor, $group)['subscribed'],
                    fn () => DB::findOneBy('subscription', [
                        'subscriber' => $actor->getId(),
                        'subscribed' => $group->getId(),
                    ], return_null: true),
                ))
            ) {
                $subscribe_form = Form::create([['subscribe', SubmitType::class, ['label' => _m('Subscribe to this group')]]]);
                $subscribe_form->handleRequest($request);
                if ($subscribe_form->isSubmitted() && $subscribe_form->isValid()) {
                    DB::persist(ActorSubscription::create([
                        'subscriber' => $actor->getId(),
                        'subscribed' => $group->getId(),
                    ]));
                    DB::flush();
                    Cache::delete(E\Actor::cacheKeys($group->getId())['subscriber']);
                    Cache::delete(E\Actor::cacheKeys($actor->getId())['subscribed']);
                    Cache::delete(ActorSubscription::cacheKeys($actor, $group)['subscribed']);
                }
            }
        }

        $notes = !\is_null($group) ? DB::dql(
            <<<'EOF'
                select n from note n
                    join activity a with n.id = a.object_id
                    join group_inbox gi with a.id = gi.activity_id
                where a.object_type = 'note' and gi.group_id = :group_id
                order by a.created desc, a.id desc
                EOF,
            ['group_id' => $group->getId()],
        ) : [];

        return [
            '_template'      => 'group/view.html.twig',
            'actor'          => $group,
            'nickname'       => $group?->getNickname() ?? $nickname,
            'notes'          => $notes,
            'subscribe_form' => $subscribe_form?->createView(),
        ];
    }

    public function groupSettings(Request $request, string $nickname)
    {
        $group = LocalGroup::getActorByNickname($nickname);
        $actor = Common::actor();
        if (!\is_null($group) && $actor->canAdmin($group)) {
            return [
                '_template'          => 'group/settings.html.twig',
                'group'              => $group,
                'personal_info_form' => ActorForms::personalInfo($request, $group)->createView(),
                'open_details_query' => $this->string('open'),
            ];
        } else {
            throw new ClientException(_m('You do not have permission to edit settings for the group "{group}"', ['{group}' => $nickname]), code: 404);
        }
    }
}
