<?php

namespace App\DataFixtures;

use App\Entity\GSActor;
use App\Entity\LocalGroup;
use App\Entity\LocalUser;
use App\Util\Nickname;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NicknameFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        foreach ([LocalUser::class => ['taken_user', 'setId'], LocalGroup::class => ['taken_group', 'setGroupId']] as $entity => [$nick, $method]) {
            $actor = GSActor::create(['nickname' => $nick, 'normalized_nickname' => Nickname::normalize($nick, check_already_used: false)]);
            $manager->persist($actor);
            $ent = $entity::create(['nickname' => $nick]);
            $ent->{$method}($actor->getId());
            $manager->persist($ent);
        }
        $manager->flush();
    }
}
