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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\UserRoles;
use App\Util\Common;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Nickname;
use DateTimeInterface;
use Exception;
use libphonenumber\PhoneNumber;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
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
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class LocalUser extends Entity implements UserInterface, PasswordAuthenticatedUserInterface
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private string $nickname;
    private ?string $password;
    private ?string $outgoing_email;
    private ?string $incoming_email;
    private ?bool $is_email_verified;
    private ?string $timezone;
    private ?PhoneNumber $phone_number;
    private ?int $sms_carrier;
    private ?string $sms_email;
    private ?bool $auto_subscribe_back;
    private ?int $subscription_policy;
    private ?bool $is_stream_private;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = \mb_substr($nickname, 0, 64);
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setPassword(?string $password): self
    {
        $this->password = \mb_substr($password, 0, 191);
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setOutgoingEmail(?string $outgoing_email): self
    {
        $this->outgoing_email = \mb_substr($outgoing_email, 0, 191);
        return $this;
    }

    public function getOutgoingEmail(): ?string
    {
        return $this->outgoing_email;
    }

    public function setIncomingEmail(?string $incoming_email): self
    {
        $this->incoming_email = \mb_substr($incoming_email, 0, 191);
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

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = \mb_substr($timezone, 0, 50);
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
        $this->sms_email = \mb_substr($sms_email, 0, 191);
        return $this;
    }

    public function getSmsEmail(): ?string
    {
        return $this->sms_email;
    }

    public function setAutoSubscribeBack(?bool $auto_subscribe_back): self
    {
        $this->auto_subscribe_back = $auto_subscribe_back;
        return $this;
    }

    public function getAutoSubscribeBack(): ?bool
    {
        return $this->auto_subscribe_back;
    }

    public function setSubscriptionPolicy(?int $subscription_policy): self
    {
        $this->subscription_policy = $subscription_policy;
        return $this;
    }

    public function getSubscriptionPolicy(): ?int
    {
        return $this->subscription_policy;
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

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    // {{{ Authentication
    /**
     * Returns the salt that was originally used to encode the password.
     * BCrypt and Argon2 generate their own salts
     */
    public function getSalt(): ?string
    {
        return null;
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

    /**
     * When authenticating, check a user's password in a timing safe
     * way. Will update the password by rehashing if deemed necessary
     */
    public function checkPassword(string $password_plain_text): bool
    {
        // Timing safe password verification
        if (password_verify($password_plain_text, $this->password)) {
            // Update old formats
            if (password_needs_rehash(
                $this->password,
                self::algoNameToConstant(Common::config('security', 'algorithm')),
                Common::config('security', 'options'),
            )
            ) {
                // @codeCoverageIgnoreStart
                $this->changePassword(null, $password_plain_text, override: true);
                // @codeCoverageIgnoreEnd
            }
            return true;
        }
        return false;
    }

    public function changePassword(?string $old_password_plain_text, string $new_password_plain_text, bool $override = false): bool
    {
        if ($override || $this->checkPassword($old_password_plain_text)) {
            $this->setPassword(self::hashPassword($new_password_plain_text));
            DB::flush();
            return true;
        }
        return false;
    }

    public static function hashPassword(string $password): string
    {
        $algorithm = self::algoNameToConstant(Common::config('security', 'algorithm'));
        $options   = Common::config('security', 'options');

        return password_hash($password, $algorithm, $options);
    }

    /**
     * Public for testing
     */
    public static function algoNameToConstant(string $algo)
    {
        switch ($algo) {
        case 'bcrypt':
        case 'argon2i':
        case 'argon2d':
        case 'argon2id':
            $c = 'PASSWORD_' . mb_strtoupper($algo);
            if (\defined($c)) {
                return \constant($c);
            }
            // fallthrough
            // no break
        default:
            throw new Exception('Unsupported or unsafe hashing algorithm requested');
        }
    }

    /**
     * Returns the username used to authenticate the user.
     * Part of the Symfony UserInterface
     *
     * @deprecated since Symfony 5.3, use getUserIdentifier() instead
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * returns the identifier for this user (e.g. its nickname)
     * Part of the Symfony UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->getNickname();
    }
    // }}} Authentication

    /**
     * Checks if desired nickname is allowed, and in case it is, it sets Actor's nickname cache to newly set nickname
     *
     * @param string $nickname Desired new nickname
     *
     * @throws NicknameEmptyException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     *
     * @return $this
     */
    public function setNicknameSanitizedAndCached(string $nickname): self
    {
        $nickname = Nickname::normalize($nickname, check_already_used: false, which: Nickname::CHECK_LOCAL_USER, check_is_allowed: true);
        $this->setNickname($nickname);
        $this->getActor()->setNickname($nickname);
        Cache::delete(self::cacheKeys($this->getId())['nickname']);
        Cache::delete(Actor::cacheKeys($this->getId())['nickname']);
        return $this;
    }

    public function getActor(): Actor
    {
        return Actor::getById($this->id);
    }

    /**
     * Returns the roles granted to the user
     */
    public function getRoles()
    {
        return UserRoles::toArray($this->getActor()->getRoles());
    }

    public static function cacheKeys(mixed $identifier): array
    {
        return [
            'id'       => "user-id-{$identifier}",
            'nickname' => "user-nickname-{$identifier}",
            'email'    => "user-email-{$identifier}",
        ];
    }

    public static function getById(int $id): ?self
    {
        return Cache::get(self::cacheKeys($id)['id'], fn () => DB::findOneBy('local_user', ['id' => $id]));
    }

    public static function getByNickname(string $nickname): ?self
    {
        $key = str_replace('_', '-', $nickname);
        return Cache::get(self::cacheKeys($key)['nickname'], fn () => DB::findOneBy('local_user', ['nickname' => $nickname]));
    }

    /**
     * @return self Returns self if email found
     */
    public static function getByEmail(string $email): ?self
    {
        $key = str_replace('@', '-', $email);
        return Cache::get(self::cacheKeys($key)['email'], fn () => DB::findOneBy('local_user', ['or' => ['outgoing_email' => $email, 'incoming_email' => $email]]));
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'local_user',
            'description' => 'local users, bots, etc',
            'fields'      => [
                'id'                  => ['type' => 'int',          'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to actor table'],
                'nickname'            => ['type' => 'varchar',      'not null' => true,    'length' => 64, 'description' => 'nickname or username, foreign key to actor'],
                'password'            => ['type' => 'varchar',      'length' => 191,       'description' => 'salted password, can be null for users with federated authentication'],
                'outgoing_email'      => ['type' => 'varchar',      'length' => 191,       'description' => 'email address for password recovery, notifications, etc.'],
                'incoming_email'      => ['type' => 'varchar',      'length' => 191,       'description' => 'email address for post-by-email'],
                'is_email_verified'   => ['type' => 'bool',         'default' => false,    'description' => 'Whether the user opened the comfirmation email'],
                'timezone'            => ['type' => 'varchar',      'length' => 50,        'description' => 'timezone'],
                'phone_number'        => ['type' => 'phone_number', 'description' => 'phone number'],
                'sms_carrier'         => ['type' => 'int',          'foreign key' => true, 'target' => 'SmsCarrier.id', 'multiplicity' => 'one to one', 'description' => 'foreign key to sms_carrier'],
                'sms_email'           => ['type' => 'varchar',      'length' => 191,       'description' => 'built from sms and carrier (see sms_carrier)'],
                'auto_subscribe_back' => ['type' => 'bool',         'default' => false,    'description' => 'automatically subscribe to users who subscribed us'],
                'subscription_policy' => ['type' => 'int',          'size' => 'tiny',      'default' => 0, 'description' => '0 = anybody can subscribe; 1 = require approval'],
                'is_stream_private'   => ['type' => 'bool',         'default' => false,    'description' => 'whether to limit all notices to subscribers only'],
                'created'             => ['type' => 'datetime',     'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'            => ['type' => 'timestamp',    'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'user_nickname_key'       => ['nickname'],
                'user_outgoing_email_key' => ['outgoing_email'],
                'user_incoming_email_key' => ['incoming_email'],
                'user_phone_number_key'   => ['phone_number'],
            ],
            'indexes' => [
                'user_nickname_idx'  => ['nickname'],
                'user_created_idx'   => ['created'],
                'user_sms_email_idx' => ['sms_email'],
            ],
        ];
    }
}
