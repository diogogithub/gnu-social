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
 * Give a warm greeting to our friendly user
 *
 * @package   GNU social
 * @author    Brion Vibber <brionv@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * This sample action shows some basic ways of doing output in an action
 * class.
 *
 * Action classes have several output methods that they override from
 * the parent class.
 *
 * @category  Sample
 * @package   GNU social
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class HelloAction extends Action
{
    public $user = null;
    public $gc = null;

    /**
     * Take arguments for running
     *
     * This method is called first, and it lets the action class get
     * all its arguments and validate them. It's also the time
     * to fetch any relevant data from the database.
     *
     * Action classes should run parent::prepare($args) as the first
     * line of this method to make sure the default argument-processing
     * happens.
     *
     * @param array $args $_REQUEST args
     *
     * @return bool success flag
     * @throws ClientException
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);

        $this->user = common_current_user();

        if (!empty($this->user)) {
            $this->gc = User_greeting_count::inc($this->user->getID());
        }

        return true;
    }

    /**
     * Handle request
     *
     * This is the main method for handling a request. Note that
     * most preparation should be done in the prepare() method;
     * by the time handle() is called the action should be
     * more or less ready to go.
     *
     * @return void
     * @throws ClientException
     * @throws ReflectionException
     * @throws ServerException
     */
    public function handle()
    {
        parent::handle();

        $this->showPage();
    }

    /**
     * Title of this page
     *
     * Override this method to show a custom title.
     *
     * @return string Title of the page
     * @throws Exception
     */
    public function title()
    {
        if (empty($this->user)) {
            // TRANS: Page title for sample plugin.
            return _m('Hello');
        } else {
            // TRANS: Page title for sample plugin. %s is a user nickname.
            return sprintf(_m('Hello, %s!'), $this->user->getNickname());
        }
    }

    /**
     * Show content in the content area
     *
     * The default StatusNet page has a lot of decorations: menus,
     * logos, tabs, all that jazz. This method is used to show
     * content in the content area of the page; it's the main
     * thing you want to overload.
     *
     * This method also demonstrates use of a plural localized string.
     *
     * @return void
     * @throws Exception
     */
    public function showContent()
    {
        if (empty($this->user)) {
            $this->element(
                'p',
                ['class' => 'greeting'],
                // TRANS: Message in sample plugin.
                _m('Hello, stranger!')
            );
        } else {
            $this->element(
                'p',
                ['class' => 'greeting'],
                // TRANS: Message in sample plugin. %s is a user nickname.
                sprintf(_m('Hello, %s'), $this->user->getNickname())
            );
            $this->element(
                'p',
                ['class' => 'greeting_count'],
                // TRANS: Message in sample plugin.
                // TRANS: %d is the number of times a user is greeted.
                sprintf(
                    _m(
                        'I have greeted you %d time.',
                        'I have greeted you %d times.',
                        $this->gc->greeting_count
                    ),
                    $this->gc->greeting_count
                )
            );
        }
    }

    /**
     * Return true if read only.
     *
     * Some actions only read from the database; others read and write.
     * The simple database load-balancer built into StatusNet will
     * direct read-only actions to database mirrors (if they are configured),
     * and read-write actions to the master database.
     *
     * This defaults to false to avoid data integrity issues, but you
     * should make sure to overload it for performance gains.
     *
     * @param array $args other arguments, if RO/RW status depends on them.
     *
     * @return bool is read only action?
     */
    public function isReadOnly($args)
    {
        return false;
    }
}
