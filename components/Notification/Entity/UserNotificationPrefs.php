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

namespace Component\Notification\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for user notification preferences
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class UserNotificationPrefs extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $user_id;
    private string $transport;
    private ?int $target_actor_id        = null;
    private bool $activity_by_subscribed = true;
    private bool $mention                = true;
    private bool $reply                  = true;
    private bool $subscription           = true;
    private bool $favorite               = true;
    private bool $nudge                  = false;
    private bool $dm                     = true;
    private bool $post_on_status_change  = false;
    private ?bool $enable_posting        = true;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setTransport(string $transport): self
    {
        $this->transport = mb_substr($transport, 0, 191);
        return $this;
    }

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function setTargetActorId(?int $target_actor_id): self
    {
        $this->target_actor_id = $target_actor_id;
        return $this;
    }

    public function getTargetActorId(): ?int
    {
        return $this->target_actor_id;
    }

    public function setActivityBySubscribed(bool $activity_by_subscribed): self
    {
        $this->activity_by_subscribed = $activity_by_subscribed;
        return $this;
    }

    public function getActivityBySubscribed(): bool
    {
        return $this->activity_by_subscribed;
    }

    public function setMention(bool $mention): self
    {
        $this->mention = $mention;
        return $this;
    }

    public function getMention(): bool
    {
        return $this->mention;
    }

    public function setReply(bool $reply): self
    {
        $this->reply = $reply;
        return $this;
    }

    public function getReply(): bool
    {
        return $this->reply;
    }

    public function setSubscription(bool $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getSubscription(): bool
    {
        return $this->subscription;
    }

    public function setFavorite(bool $favorite): self
    {
        $this->favorite = $favorite;
        return $this;
    }

    public function getFavorite(): bool
    {
        return $this->favorite;
    }

    public function setNudge(bool $nudge): self
    {
        $this->nudge = $nudge;
        return $this;
    }

    public function getNudge(): bool
    {
        return $this->nudge;
    }

    public function setDm(bool $dm): self
    {
        $this->dm = $dm;
        return $this;
    }

    public function getDm(): bool
    {
        return $this->dm;
    }

    public function setPostOnStatusChange(bool $post_on_status_change): self
    {
        $this->post_on_status_change = $post_on_status_change;
        return $this;
    }

    public function getPostOnStatusChange(): bool
    {
        return $this->post_on_status_change;
    }

    public function setEnablePosting(?bool $enable_posting): self
    {
        $this->enable_posting = $enable_posting;
        return $this;
    }

    public function getEnablePosting(): ?bool
    {
        return $this->enable_posting;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'user_notification_prefs',
            'fields' => [
                'user_id'                => ['type' => 'int',       'foreign key' => true, 'target' => 'LocalUser.id', 'multiplicity' => 'one to one', 'not null' => true],
                'transport'              => ['type' => 'varchar',   'length' => 191, 'not null' => true, 'description' => 'transport (ex email. xmpp, aim)'],
                'target_actor_id'        => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'default' => null, 'description' => 'If not null, settings are specific only to a given actors'],
                'activity_by_subscribed' => ['type' => 'bool',      'not null' => true, 'default' => true,  'description' => 'Notify when a new activity by someone we subscribe is made'],
                'mention'                => ['type' => 'bool',      'not null' => true, 'default' => true,  'description' => 'Notify when mentioned by someone we do not subscribe'],
                'reply'                  => ['type' => 'bool',      'not null' => true, 'default' => true,  'description' => 'Notify when someone replies to a notice made by us'],
                'subscription'           => ['type' => 'bool',      'not null' => true, 'default' => true,  'description' => 'Notify someone subscribes us'],
                'favorite'               => ['type' => 'bool',      'not null' => true, 'default' => true,  'description' => 'Notify someone favorites a notice by us'],
                'nudge'                  => ['type' => 'bool',      'not null' => true, 'default' => false, 'description' => 'Notify someone nudges us'],
                'dm'                     => ['type' => 'bool',      'not null' => true, 'default' => true,  'description' => 'Notify someone sends us a direct message'],
                'post_on_status_change'  => ['type' => 'bool',      'not null' => true, 'default' => false, 'description' => 'Post a notice when our status in service changes'],
                'enable_posting'         => ['type' => 'bool',      'default' => true,  'description' => 'Enable posting from this service'],
                'created'                => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'               => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['user_id', 'transport'],
            'indexes'     => [
                'user_notification_prefs_user_target_actor_idx' => ['user_id', 'target_actor_id'],
            ],
        ];
    }
}
