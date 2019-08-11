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

defined('GNUSOCIAL') || die();

/**
 * Form for subscribing to a user
 *
 * @category  Plugin
 * @package   SearchSubPlugin
 * @author    Brion Vibber <brion@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      UnsubscribeForm
 */
class SearchSubForm extends Form
{
    /**
     * Name of search to subscribe to
     */
    public $search = '';

    /**
     * Constructor
     *
     * @param Action $out output channel (usually HTMLOutputter)
     * @param string $search name of search to subscribe to
     */
    public function __construct($out = null, $search = null)
    {
        parent::__construct($out);

        $this->search = $search;
    }

    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    public function id()
    {
        return 'search-subscribe-' . $this->search;
    }


    /**
     * class of the form
     *
     * @return string of the form class
     */
    public function formClass()
    {
        // class to match existing styles...
        return 'form_user_subscribe ajax';
    }


    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    public function action()
    {
        return common_local_url('searchsub', array('search' => $this->search));
    }

    /**
     * Legend of the Form
     *
     * @return void
     * @throws Exception
     */
    public function formLegend()
    {
        // TRANS: Form legend.
        $this->out->element('legend', null, _m('Subscribe to this search'));
    }

    /**
     * Data elements of the form
     *
     * @return void
     */
    public function formData()
    {
        $this->out->hidden(
            'subscribeto-' . $this->search,
            $this->search,
            'subscribeto'
        );
    }

    /**
     * Action elements
     *
     * @return void
     * @throws Exception
     */
    public function formActions()
    {
        $this->out->submit(
            'submit',
            // TRANS: Button text for subscribing to a search.
            _m('BUTTON', 'Subscribe'),
            'submit',
            null,
            // TRANS: Button title for subscribing to a search.
            _m('Subscribe to this search.')
        );
    }
}
