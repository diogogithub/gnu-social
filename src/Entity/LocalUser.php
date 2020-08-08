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

use App\Core\DB\DB;
use App\Core\UserRoles;
use App\Util\Common;
use DateTime;
use DateTimeInterface;
use Exception;
use libphonenumber\PhoneNumber;
use Symfony\Component\Security\Core\User\UserInterface;

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
class LocalUser implements UserInterface
{
    // {{{ Autocode

    private ?string $nickname;
    private ?string $password;
    private ?string $outgoing_email;
    private ?string $incoming_email;
    private ?bool $is_email_verified;
    private ?string $language;
    private ?string $timezone;
    private ?PhoneNumber $phone_number;
    private ?int $sms_carrier;
    private ?string $sms_email;
    private ?string $uri;
    private ?bool $auto_follow_back;
    private ?int $follow_policy;
    private ?bool $is_stream_private;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

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

    public function setOutgoingEmail(?string $outgoing_email): self
    {
        $this->outgoing_email = $outgoing_email;
        return $this;
    }

    public function getOutgoingEmail(): ?string
    {
        return $this->outgoing_email;
    }

    public function setIncomingEmail(?string $incoming_email): self
    {
        $this->incoming_email = $incoming_email;
        return $this;
    }

    public function getIncomingEmail(): ?string
    {
        return $this->incoming_email;
    }

    public function setIsEmailVerified(?bool $is_email_verified): self
    {
        $this->is_email_verified = $is_email_verified;
        return $this;
    }

    public function getIsEmailVerified(): ?bool
    {
        return $this->is_email_verified;
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

    public function setPhoneNumber(?PhoneNumber $phone_number): self
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function setSmsCarrier(?int $sms_carrier): self
    {
        $this->sms_carrier = $sms_carrier;
        return $this;
    }

    public function getSmsCarrier(): ?int
    {
        return $this->sms_carrier;
    }

    public function setSmsEmail(?string $sms_email): self
    {
        $this->sms_email = $sms_email;
        return $this;
    }

    public function getSmsEmail(): ?string
    {
        return $this->sms_email;
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

    public function setAutoFollowBack(?bool $auto_follow_back): self
    {
        $this->auto_follow_back = $auto_follow_back;
        return $this;
    }

    public function getAutoFollowBack(): ?bool
    {
        return $this->auto_follow_back;
    }

    public function setFollowPolicy(?int $follow_policy): self
    {
        $this->follow_policy = $follow_policy;
        return $this;
    }

    public function getFollowPolicy(): ?int
    {
        return $this->follow_policy;
    }

    public function setIsStreamPrivate(?bool $is_stream_private): self
    {
        $this->is_stream_private = $is_stream_private;
        return $this;
    }

    public function getIsStreamPrivate(): ?bool
    {
        return $this->is_stream_private;
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

    public function __construct(string $nickname, string $email, string $password)
    {
        $this->nickname       = $nickname;
        $this->outgoing_email = $email;
        $this->incoming_email = $email;
        $this->changePassword($password, true);
        // TODO auto update created and modified
        $this->created  = new DateTime();
        $this->modified = new DateTime();
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'local_user',
            'description' => 'local users',
            'fields'      => [
                'nickname'          => ['type' => 'varchar', 'length' => 64,  'description' => 'nickname or username, duped in profile'],
                'password'          => ['type' => 'varchar', 'length' => 191, 'description' => 'salted password, can be null for OpenID users'],
                'outgoing_email'    => ['type' => 'varchar', 'length' => 191, 'description' => 'email address for password recovery, notifications, etc.'],
                'incoming_email'    => ['type' => 'varchar', 'length' => 191, 'description' => 'email address for post-by-email'],
                'is_email_verified' => ['type' => 'bool', 'default' => false, 'description' => 'Whether the user opened the comfirmation email'],
                'language'          => ['type' => 'varchar', 'length' => 50,  'description' => 'preferred language'],
                'timezone'          => ['type' => 'varchar', 'length' => 50,  'description' => 'timezone'],
                'phone_number'      => ['type' => 'phone_number', 'description' => 'phone number'],
                'sms_carrier'       => ['type' => 'int', 'description' => 'foreign key to sms_carrier'],
                'sms_email'         => ['type' => 'varchar', 'length' => 191, 'description' => 'built from sms and carrier (see sms_carrier)'],
                'uri'               => ['type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier, usually a tag URI'],
                'auto_follow_back'  => ['type' => 'bool', 'default' => false, 'description' => 'automatically follow users who follow us'],
                'follow_policy'     => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => '0 = anybody can follow; 1 = require approval'],
                'is_stream_private' => ['type' => 'bool', 'default' => false, 'description' => 'whether to limit all notices to followers only'],
                'created'           => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'          => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['nickname'],
            'unique keys' => [
                'user_outgoing_email_key' => ['outgoing_email'],
                'user_incoming_email_key' => ['incoming_email'],
                'user_phone_number_key'   => ['phone_number'],
                'user_uri_key'            => ['uri'],
            ],
            'foreign keys' => [
                'user_nickname_fkey' => ['profile', ['nickname' => 'nickname']],
                'user_carrier_fkey'  => ['sms_carrier', ['sms_carrier' => 'id']],
            ],
            'indexes' => [
                'user_nickname_idx'  => ['nickname'],
                'user_created_idx'   => ['created'],
                'user_sms_email_idx' => ['sms_email'],
            ],
        ];
    }

    public function getProfile()
    {
        return DB::findOneBy('profile', ['nickname' => $this->nickname]);
    }

    /**
     * Returns the roles granted to the user
     */
    public function getRoles()
    {
        return UserRoles::bitmapToStrings($this->getProfile()->getRoles());
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * Implemented in the auto code
     */

    /**
     * Returns the salt that was originally used to encode the password.
     * BCrypt and Argon2 generate their own salts
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     */
    public function getUsername()
    {
        return $this->nickname;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    public function checkPassword(string $new_password): bool
    {
        // Timing safe password verification
        if (password_verify($new_password, $this->password)) {
            // Update old formats
            if (password_needs_rehash($this->password,
                                      self::algoNameToConstant(Common::config('security', 'algorithm')),
                                      Common::config('security', 'options'))
            ) {
                $this->changePassword($new_password, true);
            }
            return true;
        }
        return false;
    }

    public function changePassword(string $new_password, bool $override = false): void
    {
        if ($override || $this->checkPassword($new_password)) {
            $this->setPassword($this->hashPassword($new_password));
            DB::flush();
        }
    }

    public function hashPassword(string $password)
    {
        $algorithm = self::algoNameToConstant(Common::config('security', 'algorithm'));
        $options   = Common::config('security', 'options');

        return password_hash($password, $algorithm, $options);
    }

    private static function algoNameToConstant(string $algo)
    {
        switch ($algo) {
        case 'bcrypt':
            return PASSWORD_BCRYPT;
        case 'argon2i':
        case 'argon2d':
        case 'argon2id':
            $c = 'PASSWORD_' . strtoupper($algo);
            if (defined($c)) {
                return constant($c);
            }
            // fallthrough
            // no break
        default:
            throw new Exception('Unsupported or unsafe hashing algorithm requested');
        }
    }
}
