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

class SearchSubTrackoffCommand extends Command
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

        $profile = $cur->getProfile();
        while ($all->fetch()) {
            try {
                SearchSub::cancel($profile, $all->search);
            } catch (Exception $e) {
                // TRANS: Message given having failed to cancel one of the search subs with 'track off' command.
                // TRANS: %s is the search for which the subscription removal failed.
                $channel->error($cur, sprintf(
                    _m('Error disabling search subscription for query "%s".'),
                    $all->search
                ));
                return;
            }
        }

        // TRANS: Message given having disabled all search subscriptions with 'track off'.
        $channel->output($cur, _m('Disabled all your search subscriptions.'));
    }
}
