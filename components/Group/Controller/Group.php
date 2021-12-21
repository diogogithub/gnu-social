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
use App\Core\Controller\ActorController;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Entity\Actor;
use App\Entity as E;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use App\Util\Nickname;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Group extends ActorController
{
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
        $group = Actor::getByNickname($nickname, type: Actor::GROUP);
        if (\is_null($group)) {
            $actor = Common::actor();
            if (!\is_null($actor)) {
                $form = Form::create([
                    ['create', SubmitType::class, ['label' => _m('Create this group')]],
                ]);

                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    Log::info(
                        _m(
                            'Actor id:{actor_id} nick:{actor_nick} created the group {nickname}',
                            ['{actor_id}' => $actor->getId(), 'actor_nick' => $actor->getNickname(), 'nickname' => $nickname],
                        ),
                    );

                    $group = Actor::create([
                        'nickname' => $nickname,
                        'type'     => Actor::GROUP,
                        'is_local' => true,
                    ]);
                    DB::persist($group);
                    DB::persist(E\Subscription::create([
                        'subscriber' => $group->getId(),
                        'subscribed' => $group->getId(),
                    ]));
                    DB::persist(E\Subscription::create([
                        'subscriber' => $actor->getId(),
                        'subscribed' => $group->getId(),
                    ]));
                    DB::persist(E\GroupMember::create([
                        'group_id' => $group->getId(),
                        'actor_id' => $actor->getId(),
                        'is_admin' => true,
                    ]));
                    DB::flush();
                    Cache::delete(Actor::cacheKeys($actor->getId())['subscriber']);
                    Cache::delete(Actor::cacheKeys($actor->getId())['subscribed']);
                    throw new RedirectException;
                }

                return [
                    '_template'   => 'group/view.html.twig',
                    'nickname'    => $nickname,
                    'create_form' => $form->createView(),
                ];
            }
        }

        $notes = !\is_null($group) ? DB::dql(
            <<<'EOF'
                select n from note n
                    join activity a with n.id = a.object_id
                    join group_inbox gi with a.id = gi.activity_id
                where a.object_type = 'note' and gi.group_id = :group_id
                EOF,
            ['group_id' => $group->getId()],
        ) : [];

        return [
            '_template' => 'group/view.html.twig',
            'actor'     => $group,
            'nickname'  => $group?->getNickname() ?? $nickname,
            'notes'     => $notes,
        ];
    }
}
