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

        $test_files = [
            'image.png'         => 'image/png',
            'image.gif'         => 'image/gif',
            'image.jpg'         => 'image/jpeg',
            'image.jpeg'        => 'image/jpeg',
            'office.pdf'        => 'application/pdf',
            'wordproc.odt'      => 'application/vnd.oasis.opendocument.text',
            'wordproc.ott'      => 'application/vnd.oasis.opendocument.text-template',
            'wordproc.doc'      => 'application/msword',
            'wordproc.docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'wordproc.rtf'      => 'text/rtf',
            'spreadsheet.ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
            'spreadsheet.ots'   => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'spreadsheet.xls'   => 'application/vnd.ms-excel',
            'spreadsheet.xlt'   => 'application/vnd.ms-excel',
            'spreadsheet.xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'presentation.odp'  => 'application/vnd.oasis.opendocument.presentation',
            'presentation.otp'  => 'application/vnd.oasis.opendocument.presentation-template',
            'presentation.ppt'  => 'application/vnd.ms-powerpoint',
            'presentation.pptx' => 'application/zip', //"application/vnd.openxmlformats-officedocument.presentationml.presentation",
        ];

        $store(INSTALLDIR . '/tests/Media/sample-uploads/image.jpeg', '1x1 JPEG image title');
        $manager->flush();
    }
}
