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
 * Base class for UI widgets
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2009-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Base class for UI widgets
 *
 * A widget is a cluster of HTML elements that provide some functionality
 * that's used on different parts of the site. Examples would be profile
 * lists, notice lists, navigation menus (tabsets) and common forms.
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       HTMLOutputter
 */

class Widget
{
    protected $avatarSize = AVATAR_STREAM_SIZE;

    /**
     * Action (HTMLOutputter) to use for output
     */

    public $out = null;

    /**
     * Prepare the widget for use
     *
     * @param Action $out output helper, defaults to null
     * @param array $widgetOpts
     */
    public function __construct(?Action $out = null, array $widgetOpts = [])
    {
        $this->out = $out;
        if (!array_key_exists('scoped', $widgetOpts)) {
            $this->widgetOpts['scoped'] = Profile::current();
        }
        $this->scoped = $this->widgetOpts['scoped'];
    }

    /**
     * Show the widget
     *
     * Emit the HTML for the widget, using the configured outputter.
     *
     * @return void
     */

    public function show()
    {
    }

    /**
     * Get HTMLOutputter
     *
     * Return the HTMLOutputter for the widget.
     *
     * @return HTMLOutputter the output helper
     */

    public function getOut()
    {
        return $this->out;
    }

    /**
     * Delegate output methods to the outputter attribute.
     *
     * @param string $name      Name of the method
     * @param array  $arguments Arguments called
     *
     * @return mixed Return value of the method.
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->out, $name), $arguments);
    }

    /**
     * Default avatar size for this widget.
     */
    public function avatarSize()
    {
        return $this->avatarSize;
    }

    protected function showAvatar(Profile $profile, $size=null)
    {
        $avatar_url = $profile->avatarUrl($size ?: $this->avatarSize());
        $this->out->element('img', array('src' => $avatar_url,
                                         'class' => 'avatar u-photo',
                                         'width' => $this->avatarSize(),
                                         'height' => $this->avatarSize(),
                                         'alt' => $profile->getBestName()));
    }
}
