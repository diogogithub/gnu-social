<?php

namespace App\DataFixtures;

use App\Entity\Note;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NoteFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $manager->persist(Note::create(['gsactor_id' => 1, 'content' => 'some content']));
        $manager->flush();
    }
}
