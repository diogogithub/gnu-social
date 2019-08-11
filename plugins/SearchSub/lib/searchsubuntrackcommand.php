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

class SearchSubUntrackCommand extends Command
{
    public $keyword = null;

    public function __construct($user, $keyword)
    {
        parent::__construct($user);
        $this->keyword = $keyword;
    }

    public function handle($channel)
    {
        $cur = $this->user;
        $searchsub = SearchSub::pkeyGet(array('search' => $this->keyword,
            'profile_id' => $cur->id));

        if (!$searchsub) {
            // TRANS: Error text shown a user tries to untrack a search query they're not subscribed to.
            // TRANS: %s is the keyword for the search.
            $channel->error($cur, sprintf(_m('You are not tracking the search "%s".'), $this->keyword));
            return;
        }

        try {
            SearchSub::cancel($cur->getProfile(), $this->keyword);
        } catch (Exception $e) {
            // TRANS: Message given having failed to cancel a search subscription by untrack command.
            // TRANS: %s is the keyword for the query.
            $channel->error($cur, sprintf(
                _m('Could not end a search subscription for query "%s".'),
                $this->keyword
            ));
            return;
        }

        // TRANS: Message given having removed a search subscription by untrack command.
        // TRANS: %s is the keyword for the search.
        $channel->output($cur, sprintf(
            _m('You are no longer subscribed to the search "%s".'),
            $this->keyword
        ));
    }
}
