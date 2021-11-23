<?php

declare(strict_types = 1);

namespace App\DataFixtures;

use App\Core\UserRoles;
use App\Core\VisibilityScope;
use App\Entity\Actor;
use App\Entity\GroupInbox;
use App\Entity\GroupMember;
use App\Entity\LocalGroup;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Entity\Subscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CoreFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $actors         = [];
        $local_entities = [];
        foreach ([
            'taken_user' => [LocalUser::class, 'setId', ['password' => LocalUser::hashPassword('foobar'), 'outgoing_email' => 'email@provider'], []],
            'some_user' => [LocalUser::class, 'setId', [], []],
            'admin' => [LocalUser::class, 'setId', [], ['roles' => UserRoles::ADMIN | UserRoles::USER]],
            'local_user_test_user' => [LocalUser::class, 'setId', ['password' => LocalUser::hashPassword('foobar')], []],
            'form_personal_info_test_user' => [LocalUser::class, 'setId', [], []],
            'form_account_test_user' => [LocalUser::class, 'setId', ['password' => LocalUser::hashPassword('some password')], []],
            'taken_group' => [LocalGroup::class, 'setGroupId', [], []],
        ] as $nick => [$entity, $method, $extra_create, $extra_create_actor]) {
            $actor = Actor::create(array_merge(['nickname' => $nick, 'is_local' => true], $extra_create_actor));
            $manager->persist($actor);
            $ent = $entity::create(array_merge(['nickname' => $nick], $extra_create)); // cannot use array spread for arrays with string keys
            $ent->{$method}($actor->getId());
            $local_entities[$nick] = $ent;
            $manager->persist($ent);
            // Add self subscriptions
            $manager->persist(Subscription::create(['subscriber' => $actor->getId(), 'subscribed' => $actor->getId()]));
            $actors[$nick] = $actor;
        }

        $n = Note::create(['actor_id' => $actors['taken_user']->getId(), 'content' => 'some content', 'content_type' => 'text/plain', 'is_local' => true]);
        $manager->persist($n);
        $notes   = [];
        $notes[] = Note::create(['actor_id' => $actors['taken_user']->getId(), 'content' => 'some other content', 'content_type' => 'text/plain', 'is_local' => true]);
        $notes[] = Note::create(['actor_id' => $actors['taken_user']->getId(), 'content' => 'private note', 'scope' => VisibilityScope::SUBSCRIBER, 'content_type' => 'text/plain', 'is_local' => false]);
        $notes[] = $group_note = Note::create(['actor_id' => $actors['taken_user']->getId(), 'content' => 'group note', 'scope' => VisibilityScope::GROUP, 'content_type' => 'text/plain', 'is_local' => true]);
        foreach ($notes as $note) {
            $manager->persist($note);
        }

        $manager->persist(GroupMember::create(['group_id' => $local_entities['taken_group']->getGroupId(), 'actor_id' => $actors['some_user']->getId()]));
        $manager->persist(GroupInbox::create(['group_id' => $local_entities['taken_group']->getGroupId(), 'activity_id' => $group_note->getId()]));
        $manager->flush();
    }
}
