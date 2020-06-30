<?php

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

namespace App\Entity;

use DateTimeInterface;

/**
 * Entity for users
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class User
{
    // {{{ Autocode

    private int $id;
    private ?string $nickname;
    private ?string $password;
    private ?string $email;
    private ?string $incomingemail;
    private ?bool $emailnotifysub;
    private ?int $emailnotifyfav;
    private ?bool $emailnotifynudge;
    private ?bool $emailnotifymsg;
    private ?bool $emailnotifyattn;
    private ?string $language;
    private ?string $timezone;
    private ?bool $emailpost;
    private ?string $sms;
    private ?int $carrier;
    private ?bool $smsnotify;
    private ?bool $smsreplies;
    private ?string $smsemail;
    private ?string $uri;
    private ?bool $autosubscribe;
    private ?int $subscribe_policy;
    private ?string $urlshorteningservice;
    private ?bool $private_stream;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setIncomingemail(?string $incomingemail): self
    {
        $this->incomingemail = $incomingemail;
        return $this;
    }

    public function getIncomingemail(): ?string
    {
        return $this->incomingemail;
    }

    public function setEmailnotifysub(?bool $emailnotifysub): self
    {
        $this->emailnotifysub = $emailnotifysub;
        return $this;
    }

    public function getEmailnotifysub(): ?bool
    {
        return $this->emailnotifysub;
    }

    public function setEmailnotifyfav(?int $emailnotifyfav): self
    {
        $this->emailnotifyfav = $emailnotifyfav;
        return $this;
    }

    public function getEmailnotifyfav(): ?int
    {
        return $this->emailnotifyfav;
    }

    public function setEmailnotifynudge(?bool $emailnotifynudge): self
    {
        $this->emailnotifynudge = $emailnotifynudge;
        return $this;
    }

    public function getEmailnotifynudge(): ?bool
    {
        return $this->emailnotifynudge;
    }

    public function setEmailnotifymsg(?bool $emailnotifymsg): self
    {
        $this->emailnotifymsg = $emailnotifymsg;
        return $this;
    }

    public function getEmailnotifymsg(): ?bool
    {
        return $this->emailnotifymsg;
    }

    public function setEmailnotifyattn(?bool $emailnotifyattn): self
    {
        $this->emailnotifyattn = $emailnotifyattn;
        return $this;
    }

    public function getEmailnotifyattn(): ?bool
    {
        return $this->emailnotifyattn;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setEmailpost(?bool $emailpost): self
    {
        $this->emailpost = $emailpost;
        return $this;
    }

    public function getEmailpost(): ?bool
    {
        return $this->emailpost;
    }

    public function setSms(?string $sms): self
    {
        $this->sms = $sms;
        return $this;
    }

    public function getSms(): ?string
    {
        return $this->sms;
    }

    public function setCarrier(?int $carrier): self
    {
        $this->carrier = $carrier;
        return $this;
    }

    public function getCarrier(): ?int
    {
        return $this->carrier;
    }

    public function setSmsnotify(?bool $smsnotify): self
    {
        $this->smsnotify = $smsnotify;
        return $this;
    }

    public function getSmsnotify(): ?bool
    {
        return $this->smsnotify;
    }

    public function setSmsreplies(?bool $smsreplies): self
    {
        $this->smsreplies = $smsreplies;
        return $this;
    }

    public function getSmsreplies(): ?bool
    {
        return $this->smsreplies;
    }

    public function setSmsemail(?string $smsemail): self
    {
        $this->smsemail = $smsemail;
        return $this;
    }

    public function getSmsemail(): ?string
    {
        return $this->smsemail;
    }

    public function setUri(?string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setAutosubscribe(?bool $autosubscribe): self
    {
        $this->autosubscribe = $autosubscribe;
        return $this;
    }

    public function getAutosubscribe(): ?bool
    {
        return $this->autosubscribe;
    }

    public function setSubscribePolicy(?int $subscribe_policy): self
    {
        $this->subscribe_policy = $subscribe_policy;
        return $this;
    }

    public function getSubscribePolicy(): ?int
    {
        return $this->subscribe_policy;
    }

    public function setUrlshorteningservice(?string $urlshorteningservice): self
    {
        $this->urlshorteningservice = $urlshorteningservice;
        return $this;
    }

    public function getUrlshorteningservice(): ?string
    {
        return $this->urlshorteningservice;
    }

    public function setPrivateStream(?bool $private_stream): self
    {
        $this->private_stream = $private_stream;
        return $this;
    }

    public function getPrivateStream(): ?bool
    {
        return $this->private_stream;
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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'user',
            'description' => 'local users',
            'fields'      => [
                'id'                => ['type' => 'int', 'not null' => true,  'description' => 'foreign key to profile table'],
                'nickname'          => ['type' => 'varchar', 'length' => 64,  'description' => 'nickname or username, duped in profile'],
                'password'          => ['type' => 'varchar', 'length' => 191, 'description' => 'salted password, can be null for OpenID users'],
                'outgoing_email'    => ['type' => 'varchar', 'length' => 191, 'description' => 'email address for password recovery, notifications, etc.'],
                'incoming_email'    => ['type' => 'varchar', 'length' => 191, 'description' => 'email address for post-by-email'],
                'language'          => ['type' => 'varchar', 'length' => 50,  'description' => 'preferred language'],
                'timezone'          => ['type' => 'varchar', 'length' => 50,  'description' => 'timezone'],
                'sms_phone_number'  => ['type' => 'varchar', 'length' => 64,  'description' => 'sms phone number'],
                'sms_carrier'       => ['type' => 'int', 'description' => 'foreign key to sms_carrier'],
                'sms_email'         => ['type' => 'varchar', 'length' => 191, 'description' => 'built from sms and carrier (see sms_carrier)'],
                'uri'               => ['type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier, usually a tag URI'],
                'auto_follow_back'  => ['type' => 'bool', 'default' => false, 'description' => 'automatically follow users who follow us'],
                'follow_policy'     => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => '0 = anybody can follow; 1 = require approval'],
                'is_stream_private' => ['type' => 'bool', 'default' => false, 'description' => 'whether to limit all notices to followers only'],
                'created'           => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'          => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'user_nickname_key'       => ['nickname'],
                'user_outgoing_email_key' => ['outgoing_email'],
                'user_incoming_email_key' => ['incoming_email'],
                'user_sms_key'            => ['sms_phone_number'],
                'user_uri_key'            => ['uri'],
            ],
            'foreign keys' => [
                'user_id_fkey'      => ['profile', ['id' => 'id']],
                'user_carrier_fkey' => ['sms_carrier', ['sms_carrier' => 'id']],
            ],
            'indexes' => [
                'user_created_idx'   => ['created'],
                'user_sms_email_idx' => ['sms_email'],
            ],
        ];
    }
}
