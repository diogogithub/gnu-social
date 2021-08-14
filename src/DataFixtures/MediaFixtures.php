<?php

namespace App\DataFixtures;

use App\Core\DB\DB;
use App\Core\GSFile;
use App\Util\Common;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\File;

class MediaFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        DB::setManager($manager);
        $actor = DB::findOneBy('local_user', ['nickname' => 'taken_user']);
        $store = function (string $filepath, string $title) use ($actor) {
            $copy_filepath = $filepath . '.copy';
            copy($filepath, $copy_filepath);
            $file = new File($copy_filepath, checkPath: true);
            GSFile::validateAndStoreFileAsAttachment($file, dest_dir: Common::config('attachments', 'dir') . 'test/', title: $title, actor_id: $actor->getId());
        };
        $store(INSTALLDIR . '/tests/Media/sample-uploads/image.jpeg', '1x1 JPEG image title');
        $manager->flush();
    }
}
