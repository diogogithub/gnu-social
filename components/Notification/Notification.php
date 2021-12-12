<?php

declare(strict_types=1);

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

namespace Component\Notification;

use App\Core\Event;
use App\Core\Log;
use App\Core\Modules\Component;
use App\Entity\Activity;
use App\Entity\Actor;
use Component\FreeNetwork\FreeNetwork;

class Notification extends Component
{
    /**
     * Enqueues a notification for an Actor (user or group) which means
     * it shows up in their home feed and such.
     */
    public function onNewNotification(Actor $sender, Activity $activity, array $ids_already_known = [], ?string $reason = null): bool
    {
        $targets = $activity->getNotificationTargets($ids_already_known, sender_id: $sender->getId());
        $this->notify($sender, $activity, $targets, $reason);

        return Event::next;
    }

    /**
     * Bring given Activity to Targets's attention
     *
     * @param Actor $sender
     * @param Activity $activity
     * @param array $targets
     * @param string|null $reason
     * @return bool
     */
    public function notify(Actor $sender, Activity $activity, array $targets, ?string $reason = null): bool
    {
        $remote_targets = [];
        foreach ($targets as $target) {
            if ($target->getIsLocal()) {
                if ($target->isGroup()) {
                    // FIXME: Make sure we check (for both local and remote) users are in the groups they send to!
                } else {
                    if ($target->hasBlocked($activity->getActor())) {
                        Log::info("Not saving reply to actor {$target->getId()} from sender {$sender->getId()} because of a block.");
                        continue;
                    }
                }
                // TODO: use https://symfony.com/doc/current/notifier.html
            } else {
                $remote_targets[] = $target;
            }
        }

        FreeNetwork::notify($sender, $activity, $remote_targets, $reason);

        return Event::next;
    }
}
