<?php

namespace App\DataFixtures;

use App\Core\DB\DB;
use App\Core\GSFile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Functional as F;
use Symfony\Component\HttpFoundation\File\File;

class MediaFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        DB::setManager($manager);
        F\map(glob(INSTALLDIR . '/tests/sample-uploads/*'),
              function (string $filepath) {
                  $copy_filepath = str_replace('.', '.copy.', $filepath);
                  copy($filepath, $copy_filepath);
                  $file = new File($copy_filepath, checkPath: true);
                  try {
                      GSFile::sanitizeAndStoreFileAsAttachment($file);
                  } catch (\Jcupitt\Vips\Exception $e) {
                      echo "Could not save file {$copy_filepath}\n";
                  }
                  @unlink($copy_filepath);
              });
        $manager->flush();
    }
}
