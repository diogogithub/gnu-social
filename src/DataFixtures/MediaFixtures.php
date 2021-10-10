<?php

declare(strict_types = 1);

namespace App\DataFixtures;

use App\Core\DB\DB;
use App\Core\GSFile;
use App\Util\TemporaryFile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Functional as F;

class MediaFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        DB::setManager($manager);
        F\map(
            glob(INSTALLDIR . '/tests/sample-uploads/*'),
            function (string $filepath) {
                $file = new TemporaryFile();
                $file->write(file_get_contents($filepath));
                try {
                    GSFile::storeFileAsAttachment($file);
                } catch (Exception $e) {
                    echo "Could not save file {$filepath}, failed with {$e}\n";
                } finally {
                    unset($file);
                }
            },
        );
        $manager->flush();
    }
}
