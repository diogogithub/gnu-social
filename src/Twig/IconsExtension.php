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

/**
 * GNU social Twig extensions
 *
 * @package   GNUsocial
 * @category  Twig
 *
 * @author    Ângelo D. Moura <up201303828@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Twig;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconsExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('icon',
                [$this, 'embedSvgIcon'],
                ['needs_environment' => true]
            ),
        ];
    }

    /**
     * Renders the Svg Icon template and returns it.
     *
     * @param Environment $twig
     * @param string      $icon_name
     * @param string      $icon_css_class
     *
     * @return string
     *
     * @author Ângelo D. Moura <up201303828@fe.up.pt>
     */
    public function embedSvgIcon(Environment $twig, string $icon_name = '', string $icon_css_class = '')
    {
        try {
            return $twig->render('@public_path/assets/icons/' . $icon_name . '.svg.twig', ['iconClass' => $icon_css_class]);
        } catch (LoaderError $e) {
            //return an empty string (a missing icon is not that important of an error)
            return '';
        } catch (RuntimeError $e) {
            //return an empty string (a missing icon is not that important of an error)
            return '';
        } catch (SyntaxError $e) {
            //return an empty string (a missing icon is not that important of an error)
            return '';
        }
    }
}