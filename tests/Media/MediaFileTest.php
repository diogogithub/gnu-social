<?php
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

namespace Tests\Unit;

if (!defined('INSTALLDIR')) {
    define('INSTALLDIR', dirname(dirname(__DIR__)));
}
if (!defined('PUBLICDIR')) {
    define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');
}
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}

use ClientException;
use Exception;
use MediaFile;
use PHPUnit\Framework\TestCase;
use ServerException;

require_once INSTALLDIR . '/lib/util/common.php';

final class MediaFileTest extends TestCase
{

    public function setup(): void
    {
        $this->old_attachments_supported = common_config('attachments', 'supported');
        $GLOBALS['config']['attachments']['supported'] = true;
    }

    public function tearDown(): void
    {
        $GLOBALS['config']['attachments']['supported'] = $this->old_attachments_supported;
    }

    /**
     * @dataProvider fileTypeCases
     * @param $filename
     * @param $expectedType
     * @throws ClientException
     * @throws ServerException
     */
    public function testMimeType($filename, $expectedType)
    {
        if (!file_exists($filename)) {
            throw new Exception("Test file $filename missing");
        }

        $type = MediaFile::getUploadedMimeType($filename, basename($filename));
        $this->assertEquals($expectedType, $type);
    }

    /**
     * @dataProvider fileTypeCases
     * @param $filename
     * @param $expectedType
     * @throws ClientException
     * @throws ServerException
     */
    public function testUploadedMimeType($filename, $expectedType)
    {
        if (!file_exists($filename)) {
            throw new Exception("WTF? $filename test file missing");
        }
        $tmp = tmpfile();
        fwrite($tmp, file_get_contents($filename));

        $tmp_metadata = stream_get_meta_data($tmp);
        $type = MediaFile::getUploadedMimeType($tmp_metadata['uri'], basename($filename));
        $this->assertEquals($expectedType, $type);
    }

    static public function fileTypeCases()
    {
        $base = dirname(__FILE__);
        $dir = "$base/sample-uploads";
        $files = array(
            "image.png" => "image/png",
            "image.gif" => "image/gif",
            "image.jpg" => "image/jpeg",
            "image.jpeg" => "image/jpeg",
            "office.pdf" => "application/pdf",
            "wordproc.odt" => "application/vnd.oasis.opendocument.text",
            "wordproc.ott" => "application/vnd.oasis.opendocument.text-template",
            "wordproc.doc" => "application/msword",
            "wordproc.docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "wordproc.rtf" => "text/rtf",
            "spreadsheet.ods" => "application/vnd.oasis.opendocument.spreadsheet",
            "spreadsheet.ots" => "application/vnd.oasis.opendocument.spreadsheet-template",
            "spreadsheet.xls" => "application/vnd.ms-excel",
            "spreadsheet.xlt" => "application/vnd.ms-excel",
            "spreadsheet.xlsx" =>"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "presentation.odp" => "application/vnd.oasis.opendocument.presentation",
            "presentation.otp" => "application/vnd.oasis.opendocument.presentation-template",
            "presentation.ppt" => "application/vnd.ms-powerpoint",
            "presentation.pptx" => 'application/zip', //"application/vnd.openxmlformats-officedocument.presentationml.presentation",
        );

        $dataset = array();
        foreach ($files as $file => $type) {
            $dataset[] = array("$dir/$file", $type);
        }
        return $dataset;
    }

}

