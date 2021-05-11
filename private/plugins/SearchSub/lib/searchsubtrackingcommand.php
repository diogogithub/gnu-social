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

class SearchSubTrackingCommand extends Command
{
    public function handle($channel)
    {
        $cur = $this->user;
        $all = new SearchSub();
        $all->profile_id = $cur->id;
        $all->find();

        if ($all->N == 0) {
            // TRANS: Error text shown a user tries to disable all a search subscriptions with track off command, but has none.
            $channel->error($cur, _m('You are not tracking any searches.'));
            return;
        }

        $list = array();
        while ($all->fetch()) {
            $list[] = $all->search;
        }

        // TRANS: Separator for list of tracked searches.
        $separator = _m('SEPARATOR', '", "');

        // TRANS: Message given having disabled all search subscriptions with 'track off'.
        // TRANS: %s is a list of searches. Separator default is '", "'.
        $channel->output($cur, sprintf(
            _m('You are tracking searches for: "%s".'),
            implode($separator, $list)
        ));
    }
}
