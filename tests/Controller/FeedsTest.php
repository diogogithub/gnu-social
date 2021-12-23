<?php

declare(strict_types = 1);

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

namespace App\Tests\Controller;

use App\Controller\Feeds;
use App\Core\DB\DB;
use App\Core\Security;
use App\Core\VisibilityScope;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\GNUsocialTestCase;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security as SSecurity;

class FeedsTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testPublic()
    {
        $this->testRoute('public', fn ($vis) => $vis->public || $vis->site);
    }

    public function testHome()
    {
        $this->testRoute('home', fn ($vis) => !$vis->message, ['taken_user']);
    }

    public function testFeeds()
    {
        $this->testRoute('network', fn ($vis) => $vis->public);
    }

    // TODO replies, re-enable
    // public function testReplies()
    // {
    //     $this->testRoute('replies', fn ($vis) => $vis->public, [], function () {
    //         $user = DB::findOneBy('local_user', ['nickname' => 'taken_user']);
    //         $sec = $this->getMockBuilder(SSecurity::class)->setConstructorArgs([self::$kernel->getContainer()])->getMock();
    //         $sec->method('getUser')->willReturn($user);
    //         Security::setHelper($sec, null);
    //     });
    // }

    private function testRoute(string $route, callable $visibility, array $extra_args = [], ?callable $setup_login = null)
    {
        parent::bootKernel();
        if (!\is_null($setup_login)) {
            $setup_login();
        }
        $req       = $this->createMock(Request::class);
        $req_stack = $this->createMock(RequestStack::class);
        $feeds     = new Feeds($req_stack);
        if ($route == 'home') {
            static::assertThrows(ClientException::class, fn () => $feeds->home($req));
        }
        $result = $feeds->{$route}($req, ...$extra_args);
        static::assertSame($result['_template'], 'feed/feed.html.twig');
        foreach ($result['notes'] as $n) {
            static::assertIsArray($n['replies']);
        }
        $notes = Common::flattenNoteArray($result['notes']);
        foreach ($notes as $n) {
            static::assertTrue(\get_class($n) == Note::class);
            $vis = VisibilityScope::create($n->getScope());
            static::assertTrue($visibility($vis));
        }
    }
}
