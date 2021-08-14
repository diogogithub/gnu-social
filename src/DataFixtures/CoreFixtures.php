<?php

namespace App\DataFixtures;

use App\Core\DB\DB;
use App\Core\GSFile;
use App\Core\VisibilityScope;
use App\Entity\GroupInbox;
use App\Entity\GSActor;
use App\Entity\LocalGroup;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Common;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\File;

class CoreFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $actors         = [];
        $local_entities = [];
        foreach (['taken_user' => [LocalUser::class, 'setId', ['password' => LocalUser::hashPassword('foobar'), 'outgoing_email' => 'email@provider']],
            'form_personal_info_test_user' => [LocalUser::class, 'setId', []],
            'form_account_test_user' => [LocalUser::class, 'setId', ['password' => LocalUser::hashPassword('some password')]],
            'taken_group' => [LocalGroup::class, 'setGroupId', []], ]
                 as $nick => [$entity, $method, $extra_create]) {
            $actor = GSActor::create(['nickname' => $nick]);
            $manager->persist($actor);
            $ent = $entity::create(array_merge(['nickname' => $nick], $extra_create)); // cannot use array spread for arrays with string keys
            $ent->{$method}($actor->getId());
            $local_entities[$nick] = $ent;
            $manager->persist($ent);
            $actors[$nick] = $actor;
        }

        $n = Note::create(['gsactor_id' => $actors['taken_user']->getId(), 'content' => 'some content']);
        $manager->persist($n);
        $notes[] = Note::create(['gsactor_id' => $actors['taken_user']->getId(), 'content' => 'some other content', 'reply_to' => $n->getId()]);
        $notes[] = Note::create(['gsactor_id' => $actors['taken_user']->getId(), 'content' => 'private note', 'scope' => VisibilityScope::FOLLOWER]);
        foreach ($notes as $note) {
            $manager->persist($note);
        }

        $manager->persist(GroupInbox::create(['group_id' => $local_entities['taken_group']->getGroupId(), 'activity_id' => $note->getId()]));
        $manager->flush();

        DB::setManager($manager);
        $filepath      = INSTALLDIR . '/tests/Media/sample-uploads/image.jpeg';
        $copy_filepath = $filepath . '.copy';
        copy($filepath, $copy_filepath);
        $file = new File($copy_filepath, checkPath: true);
        GSFile::sanitizeAndStoreFileAsAttachment($file, dest_dir: Common::config('attachments', 'dir') . 'test/');
        $manager->flush();
    }
}
