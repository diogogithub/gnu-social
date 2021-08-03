<?php

// {{{ License

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

// }}}

namespace App\Tests\Core;

use App\Core\DB\DB;
use App\Util\GNUsocialTestCase;

class AttachmentTest extends GNUsocialTestCase
{
    public function testNoAttachmentID()
    {
        // This calls static::bootKernel(), and creates a "client" that is acting as the browser
        $client = static::createClient();
        $client->request('GET', '/attachment');
        $this->assertResponseStatusCodeSame(404);
        $client->request('GET', '/attachment/-1');
        $this->assertResponseStatusCodeSame(404);
        $client->request('GET', '/attachment/asd');
        $this->assertResponseStatusCodeSame(404);
        $client->request('GET', '/attachment/0');
        // In the meantime, throwing ClientException doesn't actually result in the reaching the UI, as it's intercepted
        // by the helpful framework that displays the stack traces and such. This should be easily fixable when we have
        // our own error pages
        $this->assertSelectorTextContains('.stacktrace', 'ClientException');
    }

    private function testAttachment(string $suffix)
    {
        $client     = static::createClient();
        $attachment = DB::findOneBy('attachment', ['title' => '1x1 JPEG image title']);
        $crawler    = $client->request('GET', "/attachment/{$attachment->getId()}{$suffix}");
    }

    public function testAttachmentShow()
    {
        $this->testAttachment('');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('figure figcaption', '1x1 JPEG image title');
    }

    public function testAttachmentView()
    {
        $this->testAttachment('/view');
        $this->assertResponseIsSuccessful();
    }

    public function testAttachmentDownload()
    {
        $this->testAttachment('/download');
        $this->assertResponseIsSuccessful();
    }

    public function testAttachmentThumbnail()
    {
        $this->testAttachment('/thumbnail');
        $this->assertResponseIsSuccessful();
    }

    public function testAttachmentThumbnailWrongSize()
    {
        $this->testAttachment('/thumbnail?w=1&h=1');
        $this->assertSelectorTextContains('.stacktrace', 'ClientException');
        // $this->assertResponseStatusCodeSame(400);
    }
}
