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
 * StatusNet, the distributed open-source microblogging tool
 *
 * Module to add additional awesomenss to StatusNet
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Module
 * @package   StatusNet
 * @author    Jeroen De Dauw <jeroendedauw@gmail.com>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

defined('GNUSOCIAL') || die();

/**
 * Fun sample plugin: tweaks input data and adds a 'Cornify' widget to sidebar.
 *
 * @category Module
 * @package  GNUsocial
 * @author   Jeroen De Dauw <jeroendedauw@gmail.com>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class AwesomenessPlugin extends Plugin
{
    const PLUGIN_VERSION = '13.37.42';

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Awesomeness',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Jeroen De Dauw',
            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/Awesomeness',
            // TRANS: Module description for a sample plugin.
            'rawdescription' => _m('The Awesomeness plugin adds additional awesomeness ' .
                'to a GNU social installation.')
        ];
        return true;
    }

    /**
     * Add the conrnify button
     *
     * @param Action $action the current action
     *
     * @return void
     */
    public function onEndShowSections(Action $action)
    {
        $action->elementStart('div', ['id'    => 'cornify_section',
            'class' => 'section']);

        $action->raw(
            <<<EOT
                <a href="https://www.cornify.com" onclick="cornify_add();return false;">
                <img src="https://www.cornify.com/assets/cornify.gif" width="61" height="16" border="0" alt="Cornify" />
                </a>
EOT
        );

        $action->elementEnd('div');
    }

    public function onEndShowScripts(Action $action)
    {
        $action->script($this->path('js/cornify.js'));
    }

    /**
     * Hook for new-notice form processing to take our HTML goodies;
     * won't affect API posting etc.
     *
     * @param NewNoticeAction $action
     * @param User $user
     * @param string $content
     * @param array $options
     * @return bool hook return
     */
    public function onStartSaveNewNoticeWeb($action, $user, &$content, &$options)
    {
        $content = htmlspecialchars($content);
        $options['rendered'] = preg_replace("/(^|\s|-)((?:awesome|awesomeness)[\?!\.\,]?)(\s|$)/i", " <b>$2</b> ", $content);
    }
}
