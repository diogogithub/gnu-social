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
 * LaTeX note support for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\LatexNotes;

use App\Core\Event;
use App\Core\Modules\Plugin;
use PhpLatex_Parser;
use PhpLatex_Renderer_Html;

class LatexNotes extends Plugin
{
    public function onPostingAvailableContentTypes(array &$types): bool
    {
        $types['LaTeX'] = 'application/x-latex';
        return Event::next;
    }
    public function onRenderNoteContent($content, $content_type, &$rendered): bool
    {
        if ($content_type !== 'application/x-latex') {
            return Event::next;
        }
        // https://github.com/xemlock/php-latex
        $parser     = new PhpLatex_Parser();
        $parsedTree = $parser->parse($content);

        $htmlRenderer = new PhpLatex_Renderer_Html();
        $rendered     = $htmlRenderer->render($parsedTree);

        return Event::stop;
    }
}
