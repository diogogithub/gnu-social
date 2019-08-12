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
 * Plugin to enable Infinite Scrolling
 *
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class InfiniteScrollPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    public $on_next_only = false;

    function onEndShowScripts(Action $action)
    {
        $action->inlineScript('var infinite_scroll_on_next_only = ' . ($this->on_next_only?'true':'false') . ';');
        $action->inlineScript('var ajax_loader_url = "' . ($this->path('ajax-loader.gif')) . '";');
        $action->script($this->path('jquery.infinitescroll.js'));
        $action->script($this->path('infinitescroll.js'));
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'InfiniteScroll',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Craig Andrews',
            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/InfiniteScroll',
            'rawdescription' =>
            // TRANS: Plugin dscription.
            _m('Infinite Scroll adds the following functionality to your StatusNet installation: When a user scrolls towards the bottom of the page, the next page of notices is automatically retrieved and appended. This means they never need to click "Next Page", which dramatically increases stickiness.')
        ];
        return true;
    }
}
