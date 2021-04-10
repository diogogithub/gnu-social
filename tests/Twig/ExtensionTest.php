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

// }}} License

/**
 * This file test the Macro that Embeds SVG icons.
 *
 * @package   Tests
 *
 * @author    Ângelo D. Moura <up201303828@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Tests\Templates\Icons;

use App\Twig\Extension;
use App\Twig\Runtime;
use DirectoryIterator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class ExtensionTest extends KernelTestCase
{
    public function testIconsExtension()
    {
        // Get all Icon files names from "public/assets/icons"
        $icon_file_names = [];
        foreach (new DirectoryIterator('public/assets/icons/') as $file) {
            if ($file->isDot()) {
                continue;
            }
            $icon_file_names[] = $file->getFilename();
        }

        //Check if every icon file as a ".svg.twig" extension
        foreach ($icon_file_names as $icon_file_name) {
            static::assertMatchesRegularExpression('/.svg.twig/', $icon_file_name);
        }

        //Check if the function gives a valid HTML with a class attribute equal to the one passed
        static::bootKernel();
        $container = self::$kernel->getContainer()->get('test.service_container');
        $twig      = $container->get('twig');

        foreach ($icon_file_names as $icon_file_name) {
            $icon_name = basename($icon_file_name, '.svg.twig');

            $icon_template_render = $twig->render('@public_path/assets/icons/' . $icon_file_name, ['iconClass' => 'icon icon-' . $icon_name]);

            $icons_extension       = new Runtime();
            $icon_extension_render = $icons_extension->embedSvgIcon($twig, $icon_name, 'icon icon-' . $icon_name);

            static::assertSame($icon_template_render, $icon_extension_render);
        }
    }

    public function testIsCurrentRouteActive()
    {
        $req             = $this->createMock(Request::class);
        $req->attributes = new class {
            public function get(string $arg)
            {
                return 'current_route';
            }
        };
        $runtime = new Runtime;
        $runtime->setRequest($req);

        static::assertSame('active', $runtime->isCurrentRouteActive('current_route'));
        static::assertSame('', $runtime->isCurrentRouteActive('some_route', 'some_other_route'));
    }
}
