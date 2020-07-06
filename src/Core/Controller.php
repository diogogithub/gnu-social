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
 * Base class for controllers
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller extends AbstractController
{
    public function __invoke(Request $request)
    {
        $class  = get_called_class();
        $method = 'on' . ucfirst(strtolower($request->getMethod()));
        $vars   = ['request' => $request];
        Event::handle('StartTwigPopulateVars', [&$vars]);
        if (method_exists($class, $method)) {
            $vars = array_merge_recursive($vars, $class::$method($request, $vars));
        } else {
            $vars = array_merge_recursive($vars, $class::handle($request, $vars));
        }
        Event::handle('EndTwigPopulateVars', [&$vars]);
        $template = $vars['_template'];
        unset($vars['_template'], $vars['request']);

        // Respond in the the most preffered acceptable content type
        $format = $request->getFormat($request->getAcceptableContentTypes()[0]);
        switch ($format) {
        case 'html':
            return $this->render($template, $vars);
        case 'json':
            return new JsonResponse($vars);
        default:
            throw new BadRequestHttpException('Unsupported format', null, 406);
        }
    }
}
