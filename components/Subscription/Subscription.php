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

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use App\Util\Nickname;
use Component\Subscription\Controller\Subscribers as SubscribersController;
use Component\Subscription\Controller\Subscriptions as SubscriptionsController;

use Symfony\Component\HttpFoundation\Request;

class Subscription extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(id: 'actor_subscribe_add', uri_path: '/actor/subscribe/{object_id<\d+>}', target: [SubscribersController::class, 'subscribersAdd']);
        $r->connect(id: 'actor_subscribe_remove', uri_path: '/actor/unsubscribe/{object_id<\d+>}', target: [SubscribersController::class, 'subscribersRemove']);
        $r->connect(id: 'actor_subscriptions_id', uri_path: '/actor/{id<\d+>}/subscriptions', target: [SubscriptionsController::class, 'subscriptionsByActorId']);
        $r->connect(id: 'actor_subscriptions_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/subscriptions', target: [SubscriptionsController::class, 'subscriptionsByActorNickname']);
        $r->connect(id: 'actor_subscribers_id', uri_path: '/actor/{id<\d+>}/subscribers', target: [SubscribersController::class, 'subscribersByActorId']);
        $r->connect(id: 'actor_subscribers_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/subscribers', target: [SubscribersController::class, 'subscribersByActorNickname']);
        return Event::next;
    }

    /**
     * To use after Subscribe/Unsubscribe and DB::flush()
     *
     * @param Actor|int|LocalUser $subject The Actor who subscribed or unsubscribed
     * @param Actor|int|LocalUser $object  The Actor who was subscribed or unsubscribed from
     */
    public static function refreshSubscriptionCount(int|Actor|LocalUser $subject, int|Actor|LocalUser $object): array
    {
        $subscriber_id = \is_int($subject) ? $subject : $subject->getId();
        $subscribed_id = \is_int($object) ? $object : $object->getId();

        $cache_subscriber = Cache::delete(Actor::cacheKeys($subscriber_id)['subscribed']);
        $cache_subscribed = Cache::delete(Actor::cacheKeys($subscribed_id)['subscribers']);

        return [$cache_subscriber,$cache_subscribed];
    }

    /**
     * Persists a new Subscription Entity from Subject to Object (Actor being subscribed) and Activity
     *
     * A new notification is then handled, informing all interested Actors of this action
     *
     * @param Actor|int|LocalUser $subject The actor performing the subscription
     * @param Actor|int|LocalUser $object  The target of the subscription
     *
     * @throws DuplicateFoundException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return null|Activity a new Activity if changes were made
     *
     * @see self::refreshSubscriptionCount() to delete cache after this action
     */
    public static function subscribe(int|Actor|LocalUser $subject, int|Actor|LocalUser $object, string $source = 'web'): ?Activity
    {
        $subscriber_id = \is_int($subject) ? $subject : $subject->getId();
        $subscribed_id = \is_int($object) ? $object : $object->getId();
        $opts          = [
            'subscriber_id' => $subscriber_id,
            'subscribed_id' => $subscribed_id,
        ];
        $subscription = DB::findOneBy(table: Entity\ActorSubscription::class, criteria: $opts, return_null: true);
        $activity     = null;
        if (\is_null($subscription)) {
            DB::persist(Entity\ActorSubscription::create($opts));
            $activity = Activity::create([
                'actor_id'    => $subscriber_id,
                'verb'        => 'subscribe',
                'object_type' => 'actor',
                'object_id'   => $subscribed_id,
                'source'      => $source,
            ]);
            DB::persist($activity);

            Event::handle('NewNotification', [
                \is_int($subject) ? $subject : Actor::getById($subscriber_id),
                $activity,
                ['object' => [$activity->getObjectId()]],
                _m('{subject} subscribed to {object}.', ['{subject}' => $activity->getActorId(), '{object}' => $activity->getObjectId()]),
            ]);
        }
        return $activity;
    }

    /**
     * Removes the Subscription Entity created beforehand, by the same Actor, and on the same object
     *
     * Informs all interested Actors of this action, handling out the NewNotification event
     *
     * @param Actor|int|LocalUser $subject The actor undoing the subscription
     * @param Actor|int|LocalUser $object  The target of the subscription
     *
     * @throws DuplicateFoundException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return null|Activity a new Activity if changes were made
     *
     * @see self::refreshSubscriptionCount() to delete cache after this action
     */
    public static function unsubscribe(int|Actor|LocalUser $subject, int|Actor|LocalUser $object, string $source = 'web'): ?Activity
    {
        $subscriber_id = \is_int($subject) ? $subject : $subject->getId();
        $subscribed_id = \is_int($object) ? $object : $object->getId();
        $opts          = [
            'subscriber_id' => $subscriber_id,
            'subscribed_id' => $subscribed_id,
        ];
        $subscription = DB::findOneBy(table: Entity\ActorSubscription::class, criteria: $opts, return_null: true);
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

            Event::handle('NewNotification', [
                \is_int($subject) ? $subject : Actor::getById($subscriber_id),
                $activity,
                ['object' => [$previous_follow_activity->getObjectId()]],
                _m('{subject} unsubscribed from {object}.', ['{subject}' => $activity->getActorId(), '{object}' => $previous_follow_activity->getObjectId()]),
            ]);
        }
        return $activity;
    }

    /**
     * Provides ``\App\templates\cards\profile\view.html.twig`` an **additional action** to be performed **on the given
     * Actor** (which the profile card of is currently being rendered).
     *
     * In the case of ``\App\Component\Subscription``, the action added allows a **LocalUser** to **subscribe** or
     * **unsubscribe** a given **Actor**.
     *
     * @param Actor $object  The Actor on which the action is to be performed
     * @param array $actions An array containing all actions added to the
     *                       current profile, this event adds an action to it
     *
     * @throws DuplicateFoundException
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return bool EventHook
     */
    public function onAddProfileActions(Request $request, Actor $object, array &$actions): bool
    {
        // Action requires a user to be logged in
        // We know it's a LocalUser, which has the same id as Actor
        // We don't want the Actor to unfollow itself
        if ((\is_null($subject = Common::user())) || ($subject->getId() === $object->getId())) {
            return Event::next;
        }

        // Let's retrieve from here this subject came from to redirect it to previous location
        $from = $request->query->has('from')
                ? $request->query->get('from')
                : $request->getPathInfo();

        // Who is the subject attempting to subscribe to?
        $object_id = $object->getId();

        // The id of both the subject and object
        $opts = [
            'subscriber_id' => $subject->getId(),
            'subscribed_id' => $object_id,
        ];

        // If subject is not subbed to object already, then route it to add subscription
        // Else, route to remove subscription
        $subscribe_action_url = ($not_subscribed_already = \is_null(DB::findOneBy(table: Entity\ActorSubscription::class, criteria: $opts, return_null: true))) ? Router::url(
            'actor_subscribe_add',
            [
                'object_id' => $object_id,
                'from'      => $from . '#profile-' . $object_id,
            ],
            Router::ABSOLUTE_PATH,
        ) : Router::url(
            'actor_subscribe_remove',
            [
                'object_id' => $object_id,
                'from'      => $from . '#profile-' . $object_id,
            ],
            Router::ABSOLUTE_PATH,
        );

        // Finally, create an array with proper keys set accordingly
        // to provide Profile Card template, the info it needs in order to render it properly
        $action_extra_class = $not_subscribed_already ? 'add-actor-button-container' : 'remove-actor-button-container';
        $title              = $not_subscribed_already ? 'Subscribe ' . $object->getNickname() : 'Unsubscribe ' . $object->getNickname();
        $subscribe_action   = [
            'url'     => $subscribe_action_url,
            'title'   => _m($title),
            'classes' => 'button-container note-actions-unset ' . $action_extra_class,
            'id'      => 'add-actor-button-container-' . $object_id,
        ];

        $actions[] = $subscribe_action;

        return Event::next;
    }
}
