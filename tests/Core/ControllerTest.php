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

use App\Util\GNUsocialTestCase;
use Jchook\AssertThrows\AssertThrows;

class ControllerTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testJSONRequest()
    {
        // `server` will populate $_SERVER on the other side
        $client = static::createClient(options: [], server: ['HTTP_ACCEPT' => 'application/json']);
        $client->request('GET', '/main/all');
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        static::assertTrue($response->headers->contains('Content-Type', 'application/json'));
        static::assertJson($response->getContent());
        $json = json_decode($response->getContent(), associative: true);
        static::assertTrue(isset($json['notes']));
        static::assertTrue(isset($json['notes'][0]['note']));
        // TODO re-enable test
        // static::assertSame($json['notes'][0]['note']['content'], 'some content');
    }

    public function testUnsupported()
    {
        $client = static::createClient(options: [], server: ['HTTP_ACCEPT' => 'application/xml']);
        $client->request('GET', '/main/all');
        // $this->assertResponseStatusCodeSame(406);
        $this->assertSelectorTextContains('.stacktrace', 'ClientException');
    }
}
