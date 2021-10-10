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

namespace App\Tests\Core;

use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Util\GNUsocialTestCase;
use ReflectionClass;
use Symfony\Component\Routing\Route as SRoute;

class RouterTest extends GNUsocialTestCase
{
    public function testRouter()
    {
        parent::bootKernel();
        $rl = new RouteLoader();
        $rl->load('', null); // parameters ignored

        $rl->connect(id: 'test_route', uri_path: '/test/{id<\d+>}', target: []);

        $refl = (new ReflectionClass($rl))->getProperty('rc');
        $refl->setAccessible(true);
        $routes = $refl->getValue($rl)->all();
        static::assertIsArray($routes);

        static::assertInstanceOf(SRoute::class, $routes['test_route']);
        static::assertSame('/test/{id}', $routes['test_route']->getPath());
    }

    public function testURLGen()
    {
        parent::bootKernel();
        static::assertSame('/thumbnail/1', Router::url('thumbnail', ['id' => 1]));
    }
}
