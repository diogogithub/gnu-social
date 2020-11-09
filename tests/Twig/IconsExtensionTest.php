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
 * This file test the Macro that Embeds SVG icons.
 *
 * @package   Tests
 *
 * @author    Ângelo D. Moura <up201303828@fe.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Tests\Templates\Icons;

use App\Twig\IconsExtension;
use DirectoryIterator;
use PHPUnit\Framework\TestCase;

class IconsExtensionTest extends TestCase
{
    public function testIconsExtension()
    {

        //Get all Icon files names from "public/assets/icons"
        $icon_file_names = [];
        foreach (new DirectoryIterator('public/assets/icons/') as $file) {
            if ($file->isDot()) {
                continue;
            }
            $icon_file_names[] = $file->getFilename();
        }

        //Check if every icon file as a ".svg.twig" extension
        foreach ($icon_file_names as $icon_file_name) {
            static::assertRegExp('#([a-zA-Z0-9\s_\\.\-\(\):])+(.svg.twig)$#', $icon_file_name);
        }

        //Check if the function gives a valid HTML with a class attribute equal to the one passed
        $twig = self::$kernel->getContainer()->get('twig');

        /*
        $icon_template_render = $twig->render('public/icons/logo.svg', ['iconClass' => 'icon icon-logo']);

        $iconsExtension= new IconsExtension();

        $iconsExtension_render = $iconsExtension->embedSvgIcon($twig, 'logo', 'logo');

        self::assertEquals($icon_template_render, $iconsExtension_render);

        // Next I need to verify that the $iconsExtension_render is a valid html code (maybe through regex)

        */
    }
}
