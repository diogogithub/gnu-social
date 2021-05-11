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
 * Table Definition for subscription_queue
 */

defined('GNUSOCIAL') || die();

class Subscription_queue extends Managed_DataObject
{
    public $__table = 'subscription_queue';       // table name
    public $subscriber;
    public $subscribed;
    public $created;

    public static function schemaDef()
    {
        return array(
            'description' => 'Holder for subscription requests awaiting moderation.',
            'fields' => array(
                'subscriber' => array('type' => 'int', 'not null' => true, 'description' => 'remote or local profile making the request'),
                'subscribed' => array('type' => 'int', 'not null' => true, 'description' => 'remote or local profile being subscribed to'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
            ),
            'primary key' => array('subscriber', 'subscribed'),
            'indexes' => array(
                'subscription_queue_subscriber_created_idx' => array('subscriber', 'created'),
                'subscription_queue_subscribed_created_idx' => array('subscribed', 'created'),
            ),
            'foreign keys' => array(
                'subscription_queue_subscriber_fkey' => array('profile', array('subscriber' => 'id')),
                'subscription_queue_subscribed_fkey' => array('profile', array('subscribed' => 'id')),
            )
        );
    }

    public static function saveNew(Profile $subscriber, Profile $subscribed)
    {
        if (self::exists($subscriber, $subscribed)) {
            throw new AlreadyFulfilledException(_('This subscription request is already in progress.'));
        }
        $rq = new Subscription_queue();
        $rq->subscriber = $subscriber->id;
        $rq->subscribed = $subscribed->id;
        $rq->created = common_sql_now();
        $rq->insert();
        return $rq;
    }

    public static function exists(Profile $subscriber, Profile $other)
    {
        $sub = Subscription_queue::pkeyGet(array('subscriber' => $subscriber->getID(),
                                                 'subscribed' => $other->getID()));
        return ($sub instanceof Subscription_queue);
    }

    public static function getSubQueue(Profile $subscriber, Profile $other)
    {
        // This is essentially a pkeyGet but we have an object to return in NoResultException
        $sub = new Subscription_queue();
        $sub->subscriber = $subscriber->id;
        $sub->subscribed = $other->id;
        if (!$sub->find(true)) {
            throw new NoResultException($sub);
        }
        return $sub;
    }

    /**
     * Complete a pending subscription, as we've got approval of some sort.
     *
     * @return Subscription
     */
    public function complete()
    {
        $subscriber = Profile::getKV('id', $this->subscriber);
        $subscribed = Profile::getKV('id', $this->subscribed);
        try {
            $sub = Subscription::start($subscriber, $subscribed, Subscription::FORCE);
            $this->delete();
        } catch (AlreadyFulfilledException $e) {
            common_debug('Tried to start a subscription which already existed.');
        }
        return $sub;
    }

    /**
     * Cancel an outstanding subscription request to the other profile.
     */
    public function abort()
    {
        $subscriber = Profile::getKV('id', $this->subscriber);
        $subscribed = Profile::getKV('id', $this->subscribed);
        if (Event::handle('StartCancelSubscription', array($subscriber, $subscribed))) {
            $this->delete();
            Event::handle('EndCancelSubscription', array($subscriber, $subscribed));
        }
    }

    /**
     * Send notifications via email etc to group administrators about
     * this exciting new pending moderation queue item!
     */
    public function notify()
    {
        $other = Profile::getKV('id', $this->subscriber);
        $listenee = User::getKV('id', $this->subscribed);
        mail_subscribe_pending_notify_profile($listenee, $other);
    }
}
