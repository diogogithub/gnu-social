<?php

declare(strict_types = 1);

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

namespace Component\Subscription;

use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Exception\ServerException;
use App\Util\Nickname;
use Component\Subscription\Controller\Subscribers;
use Component\Subscription\Controller\Subscriptions;

class Subscription extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(id: 'actor_subscriptions_id', uri_path: '/actor/{id<\d+>}/subscriptions', target: [Subscriptions::class, 'subscriptionsByActorId']);
        $r->connect(id: 'actor_subscriptions_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/subscriptions', target: [Subscriptions::class, 'subscriptionsByActorNickname']);
        $r->connect(id: 'actor_subscribers_id', uri_path: '/actor/{id<\d+>}/subscribers', target: [Subscribers::class, 'subscribersByActorId']);
        $r->connect(id: 'actor_subscribers_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/subscribers', target: [Subscribers::class, 'subscribersByActorNickname']);
        return Event::next;
    }

    /**
     * Persists a new Subscription Entity from Subscriber to Subject (Actor being subscribed) and Activity
     *
     * A new notification is then handled, informing all interested Actors of this action
     *
     * @throws ServerException
     */
    public static function subscribe(int|Actor|LocalUser $subscriber, int|Actor|LocalUser $subject, string $source = 'web'): ?Activity
    {
        $subscriber_id = \is_int($subscriber) ? $subscriber : $subscriber->getId();
        $subscribed_id = \is_int($subject) ? $subject : $subject->getId();
        $opts          = [
            'subscriber_id' => $subscriber_id,
            'subscribed_id' => $subscribed_id,
        ];
        $subscription = DB::findOneBy(table: \Component\Subscription\Entity\Subscription::class, criteria: $opts, return_null: true);
        $activity     = null;
        if (\is_null($subscription)) {
            DB::persist(\Component\Subscription\Entity\Subscription::create($opts));
            $activity = Activity::create([
                'actor_id'    => $subscriber_id,
                'verb'        => 'subscribe',
                'object_type' => 'actor',
                'object_id'   => $subscribed_id,
                'source'      => $source,
            ]);
            DB::persist($activity);

            Event::handle('NewNotification', [
                $actor = ($subscriber instanceof Actor ? $subscriber : Actor::getById($subscribed_id)),
                $activity,
                ['object' => [$subscribed_id]],
                _m('{nickname} subscribed to {subject}.', ['{actor}' => $actor->getId(), '{subject}' => $activity->getObjectId()]),
            ]);
        }
        return $activity;
    }

    /**
     * Removes the Subscription Entity created beforehand, by the same Actor, and on the same subject
     *
     * Informs all interested Actors of this action, handling out the NewNotification event
     *
     * @throws ServerException
     */
    public static function unsubscribe(int|Actor|LocalUser $subscriber, int|Actor|LocalUser $subject, string $source = 'web'): ?Activity
    {
        $subscriber_id = \is_int($subscriber) ? $subscriber : $subscriber->getId();
        $subscribed_id = \is_int($subject) ? $subject : $subject->getId();
        $opts          = [
            'subscriber_id' => $subscriber_id,
            'subscribed_id' => $subscribed_id,
        ];
        $subscription = DB::findOneBy(table: \Component\Subscription\Entity\Subscription::class, criteria: $opts, return_null: true);
        $activity     = null;
        if (!\is_null($subscription)) {
            // Remove Subscription
            DB::remove($subscription);
            $previous_follow_activity = DB::findBy('activity', ['verb' => 'subscribe', 'object_type' => 'actor', 'object_id' => $subscribed_id], order_by: ['created' => 'DESC'])[0];
            // Store Activity
            $activity = Activity::create([
                'actor_id'    => $subscriber_id,
                'verb'        => 'undo',
                'object_type' => 'activity',
                'object_id'   => $previous_follow_activity->getId(),
                'source'      => $source,
            ]);
            DB::persist($activity);
        }
        return $activity;
    }
}
