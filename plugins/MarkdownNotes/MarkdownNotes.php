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
 * Markdown note support for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\MarkdownNotes;

use App\Core\Event;
use App\Core\Modules\Plugin;
use Parsedown;

class MarkdownNotes extends Plugin
{
    public function onPostingAvailableContentTypes(array &$types): bool
    {
        $types['Markdown'] = 'text/markdown';
        return Event::next;
    }
    public function onRenderNoteContent($content, $content_type, &$rendered): bool
    {
        if ($content_type !== 'text/markdown') {
            return Event::next;
        }
        // https://github.com/erusev/parsedown
        $Parsedown = new Parsedown();
        $Parsedown->setSafeMode(true);
        $rendered = $Parsedown->text($content);
        return Event::stop;
    }
}
