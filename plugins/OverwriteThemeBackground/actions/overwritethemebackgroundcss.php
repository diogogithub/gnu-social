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
 * Action with the CSS preferences
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Route with CSS that will come after theme's css in order to overwrite it with sysadmin's custom background preferences
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class overwritethemebackgroundcssAction extends Action
{
    protected $needLogin = false;
    protected $canPost = false;
    // Eventual custom background sysadmin may have set
    private $_theme_background_url = false;
    private $_background_colour = false;
    private $_background_colour_important = false;

    /**
     * Fills _theme_background_url if possible.
     *
     * @param array $args $_REQUEST array
     * @return bool true
     * @throws ClientException
     */
    protected function prepare(array $args = []): bool
    {
        parent::prepare($args);

        if (GNUsocial::isHTTPS()) {
            $background_url = common_config('overwritethemebackground', 'sslbackground-image');
            if (empty($background_url)) {
                // if background is an uploaded file, try to fall back to HTTPS file URL
                $http_url = common_config('overwritethemebackground', 'background-image');
                if (!empty($http_url)) {
                    try {
                        $f = File::getByUrl($http_url);
                        if (!empty($f->filename)) {
                            // this will handle the HTTPS case
                            $background_url = File::url($f->filename);
                        }
                    } catch (NoResultException $e) {
                        // no match
                    }
                }
            }
        } else {
            $background_url = common_config('overwritethemebackground', 'background-image');
        }

        $this->_background_colour = common_config('overwritethemebackground', 'background-color');
        if (empty($background_url)) {
            if (!empty($this->_background_colour)) {
                $this->_background_colour_important = true; // We want the colour to override theme's default background
                return true;
            }
            /*if (file_exists(Theme::file('images/bg.png'))) {
                // This should handle the HTTPS case internally
                $background_url = Theme::path('images/bg.png');
            }

            if (!empty($background_url)) {
                $this->_theme_background_url = $background_url;
            }*/
        } else {
            $this->_theme_background_url = $background_url;
        }

        return true;
    }

    /**
     * Is this action read-only?
     *
     * @param array $args other arguments dummy
     * @return bool true
     */
    public function isReadOnly($args): bool
    {
        return true;
    }

    /**
     * Print the CSS
     */
    public function handle(): void
    {
        $background_position_options = [
            'initial',
            'left top',
            'left center',
            'left bottom',
            'right top',
            'right center',
            'right bottom',
            'center top',
            'center center',
            'center bottom'
        ];
        header("Content-type: text/css", true);
        $background_color = $this->_background_colour;
        $background_image = $this->_theme_background_url;
        $background_repeat = ['repeat', 'repeat-x', 'repeat-y', 'no-repeat'][common_config('overwritethemebackground', 'background-repeat')];
        $background_position = $background_position_options[common_config('overwritethemebackground', 'background-position')];
        $background_attachment = ['scroll', 'fixed'][common_config('overwritethemebackground', 'background-attachment')];
        $css = 'body {';
        if ($background_color) {
            if (!$this->_background_colour_important) {
                $css .= 'background-color: ' . $background_color . ';';
            } else {
                $css .= 'background: ' . $background_color . ' !important;';
            }
        }
        if ($background_image) {
            $css .= 'background-image: url(' . $background_image . ');';
        }
        if ($background_repeat) {
            $css .= 'background-repeat: ' . $background_repeat . ';';
        }
        if ($background_position) {
            $css .= 'background-position: ' . $background_position . ';';
        }
        if ($background_attachment) {
            $css .= 'background-attachment: ' . $background_attachment . ';';
        }
        $css .= '}';

        echo $css;
    }
}
