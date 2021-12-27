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

/**
 * GNU social Twig extensions
 *
 * @package   GNUsocial
 * @category  Twig
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class Extension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            // new TwigFilter('foo', [GSRuntime::class, 'foo']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('instanceof', [Runtime::class, 'isInstanceOf']),
        ];
    }

    /**
     * get twig functions
     *
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            /** Twig function to output the 'active' class if the current route matches the given route */
            new TwigFunction('active', [Runtime::class, 'isCurrentRouteActive']),
            new TwigFunction('config', [Runtime::class, 'getConfig']),
            new TwigFunction('dd', 'dd'),
            new TwigFunction('die', 'die'),
            new TwigFunction('get_extra_note_actions', [Runtime::class, 'getExtraNoteActions']),
            new TwigFunction('get_feeds', [Runtime::class, 'getFeeds']),
            new TwigFunction('get_note_actions', [Runtime::class, 'getNoteActions']),
            new TwigFunction('handle_event', [Runtime::class, 'handleEvent']),
            new TwigFunction('handle_override_stylesheet', [Runtime::class, 'handleOverrideStylesheet']),
            new TwigFunction('handle_override_template_import', [Runtime::class, 'handleOverrideTemplateImport']),
            new TwigFunction('icon', [Runtime::class, 'embedSvgIcon'], ['needs_environment' => true]),
            new TwigFunction('is_firefox', [Runtime::class, 'isFirefox']),
            new TwigFunction('is_route', [Runtime::class, 'isCurrentRoute']),
            new TwigFunction('mention', [Runtime::class, 'mention']),
            new TwigFunction('open_details', [Runtime::class, 'openDetails']),
            new TwigFunction('show_stylesheets', [Runtime::class, 'getShowStylesheets']),
        ];
    }
}
