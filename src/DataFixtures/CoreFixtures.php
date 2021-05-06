<?php

namespace App\DataFixtures;

use App\Entity\GroupInbox;
use App\Entity\GSActor;
use App\Entity\LocalGroup;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Nickname;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CoreFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $actors         = [];
        $local_entities = [];
        foreach ([LocalUser::class => ['taken_user', 'setId'], LocalGroup::class => ['taken_group', 'setGroupId']] as $entity => [$nick, $method]) {
            $actor = GSActor::create(['nickname' => $nick, 'normalized_nickname' => Nickname::normalize($nick, check_already_used: false)]);
            $manager->persist($actor);
            $ent = $entity::create(['nickname' => $nick]);
            $ent->{$method}($actor->getId());
            $local_entities[$nick] = $ent;
            $manager->persist($ent);
            $actors[$nick] = $actor;
        }

        $note = Note::create(['gsactor_id' => $actors['taken_user']->getId(), 'content' => 'some content']);
        $manager->persist($note);

        $manager->persist(GroupInbox::create(['group_id' => $local_entities['taken_group']->getGroupId(), 'activity_id' => $note->getId()]));
        $manager->flush();
    }
}
