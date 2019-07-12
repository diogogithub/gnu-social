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

/**
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
// namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

define('INSTALLDIR', realpath(__DIR__ . '/../../..'));
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}
require_once INSTALLDIR . '/lib/common.php';

final class oEmbedTest extends TestCase
{
    /**
     * Run tests
     *
     * @param string $url
     * @param string $expectedType
     * @dataProvider sources
     */
    public function testEmbed($url, $expectedType)
    {
        try {
            $data = EmbedHelper::getObject($url);
            $this->assertEquals($expectedType, $data->type);
            if ($data->type == 'photo') {
                $this->assertTrue(!empty($data->thumbnail_url), 'Photo must have a URL.');
                $this->assertTrue(!empty($data->thumbnail_width), 'Photo must have a width.');
                $this->assertTrue(!empty($data->thumbnail_height), 'Photo must have a height.');
            } elseif ($data->type == 'video') {
                $this->assertTrue(!empty($data->html), 'Video must have embedding HTML.');
                $this->assertTrue(!empty($data->thumbnail_url), 'Video should have a thumbnail.');
            } else {
                $this->assertTrue(!empty($data->title), 'Page must have a title');
                $this->assertTrue(!empty($data->url), 'Page must have a URL');
            }
            if (!empty($data->thumbnail_url)) {
                $this->assertTrue(!empty($data->thumbnail_width), 'Thumbnail must list a width.');
                $this->assertTrue(!empty($data->thumbnail_height), 'Thumbnail must list a height.');
            }
        } catch (Exception $e) {
            if ($expectedType == 'none') {
                $this->assertEquals($expectedType, 'none', 'Should not have data for this URL.');
            } else {
                throw $e;
            }
        }
    }

    public static function sources() {
        return [
            ['https://notabug.org/', 'link'],
            ['http://www.youtube.com/watch?v=eUgLR232Cnw', 'video'],
            ['https://gnu.io/', 'link'],
            ['https://www.gnu.org/graphics/heckert_gnu.transp.small.png', 'photo'],
            ['http://vimeo.com/9283184', 'video'],
            ['http://leuksman.com/log/2010/10/29/statusnet-0-9-6-release/', 'none'],
            ['https://github.com/git/git/commit/85e9c7e1d42849c5c3084a9da748608468310c0e', 'link']
        ];
    }
}
