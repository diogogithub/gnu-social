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

namespace App\Util;

use App\Entity\LocalUser;
use App\Util\Exception\NicknameEmptyException;
use App\Util\Exception\NicknameException;
use App\Util\Exception\NicknameInvalidException;
use App\Util\Exception\NicknameNotAllowedException;
use App\Util\Exception\NicknameTakenException;
use App\Util\Exception\NicknameTooLongException;
use App\Util\Exception\NicknameTooShortException;
use App\Util\Exception\NotImplementedException;
use Functional as F;
use InvalidArgumentException;

/**
 * Nickname validation
 *
 * @category  Validation
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Brion Vibber <brion@pobox.com>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Nym Coy <nymcoy@gmail.com>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Daniel Supernault <danielsupernault@gmail.com>
 * @author    Diogo Cordeiro <mail@diogo.site>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2018-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Nickname
{
    /**
     * Maximum number of characters in a canonical-form nickname. Changes must validate regexs
     */
    const MAX_LEN = 64;

    /**
     * Regex fragment for pulling a formated nickname *OR* ID number.
     * Suitable for router def of 'id' parameters on API actions.
     *
     * Not guaranteed to be valid after normalization; run the string through
     * Nickname::normalize() to get the canonical form, or Nickname::isValid()
     * if you just need to check if it's properly formatted.
     *
     * This, DISPLAY_FMT, and CANONICAL_FMT should not be enclosed in []s.
     *
     * @fixme would prefer to define in reference to the other constants
     */
    public const INPUT_FMT = '(?:[0-9]+|[0-9a-zA-Z_]{1,' . self::MAX_LEN . '})';

    /**
     * Regex fragment for acceptable user-formatted variant of a nickname.
     *
     * This includes some chars such as underscore which will be removed
     * from the normalized canonical form, but still must fit within
     * field length limits.
     *
     * Not guaranteed to be valid after normalization; run the string through
     * Nickname::normalize() to get the canonical form, or Nickname::isValid()
     * if you just need to check if it's properly formatted.
     *
     * This, INPUT_FMT and CANONICAL_FMT should not be enclosed in []s.
     */
    public const DISPLAY_FMT = '[0-9a-zA-Z_]{1,' . self::MAX_LEN . '}';

    /**
     * Simplified regex fragment for acceptable full WebFinger ID of a user
     *
     * We could probably use an email regex here, but mainly we are interested
     * in matching it in our URLs, like https://social.example/user@example.com
     */
    public const WEBFINGER_FMT = '(?:\w+[\w\-\_\.]*)?\w+\@' . URL_REGEX_DOMAIN_NAME;

    /**
     * Regex fragment for checking a canonical nickname.
     *
     * Any non-matching string is not a valid canonical/normalized nickname.
     * Matching strings are valid and canonical form, but may still be
     * unavailable for registration due to blacklisting et.
     *
     * Only the canonical forms should be stored as keys in the database;
     * there are multiple possible denormalized forms for each valid
     * canonical-form name.
     *
     * This, INPUT_FMT and DISPLAY_FMT should not be enclosed in []s.
     */
    const CANONICAL_FMT = '[0-9a-z]{1,' . self::MAX_LEN . '}';

    /**
     * Regex with non-capturing group that matches whitespace and some
     * characters which are allowed right before an @ or ! when mentioning
     * other users. Like: 'This goes out to:@mmn (@chimo too) (!awwyiss).'
     *
     * FIXME: Make this so you can have multiple whitespace but not multiple
     * parenthesis or something. '(((@n_n@)))' might as well be a smiley.
     */
    public const BEFORE_MENTIONS = '(?:^|[\s\.\,\:\;\[\(]+)';

    public const CHECK_LOCAL_USER  = 1;
    public const CHECK_LOCAL_GROUP = 2;

    /**
     * Check if a nickname is valid or throw exceptions if it's not.
     * Can optionally check if the nickname is currently in use
     * @param string $nickname
     * @param bool $check_already_used
     * @param int $which
     * @param bool $check_is_allowed
     * @return bool
     * @throws NicknameEmptyException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     */
    public static function validate(string $nickname, bool $check_already_used = false, int $which = self::CHECK_LOCAL_USER, bool $check_is_allowed = true): bool
    {
        $length = mb_strlen($nickname);

        if ($length < 1) {
            throw new NicknameEmptyException();
        } else {
            if ($length > self::MAX_LEN) {
                throw new NicknameTooLongException();
            } elseif ($check_is_allowed && self::isBlacklisted($nickname)) {
                throw new NicknameNotAllowedException();
            } elseif ($check_already_used) {
                switch ($which) {
                    case self::CHECK_LOCAL_USER:
                        $lu = LocalUser::getWithPK(['nickname' => $nickname]);
                        if ($lu !== null) {
                            throw new NicknameTakenException($lu->getActor());
                        }
                        break;
                    // @codeCoverageIgnoreStart
                case self::CHECK_LOCAL_GROUP:
                    throw new NotImplementedException();
                    break;
                default:
                    throw new InvalidArgumentException();
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        return true;
    }

    /**
     * Normalize input $nickname to its canonical form and validates it.
     * The canonical form will be returned, or an exception thrown if invalid.
     *
     * @throws NicknameEmptyException
     * @throws NicknameException         (base class)
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     */
    public static function normalize(string $nickname, bool $check_already_used = false, int $which = self::CHECK_LOCAL_USER, bool $check_is_allowed = true): string
    {
        $nickname = trim($nickname);
        $nickname = str_replace('_', '', $nickname);
        $nickname = mb_strtolower($nickname);
        // We could do UTF-8 normalization (å to a, etc.) with something like Normalizer::normalize($nickname, Normalizer::FORM_C)
        // We won't as it could confuse tremendously the user, he must know what is valid and should fix his own input

        if (!self::validate(nickname: $nickname, check_already_used: $check_already_used, which: $which, check_is_allowed: $check_is_allowed) || !self::isCanonical($nickname)) {
            throw new NicknameInvalidException();
        }

        return $nickname;
    }

    /**
     * Nice simple check of whether the given string is a valid input nickname,
     * which can be normalized into an internally canonical form.
     *
     * Note that valid nicknames may be in use or blacklisted.
     *
     * @return bool True if nickname is valid. False if invalid (or taken if $check_already_used == true).
     */
    public static function isValid(string $nickname, bool $check_already_used = false, int $which = self::CHECK_LOCAL_USER, bool $check_is_allowed = true): bool
    {
        try {
            self::normalize(nickname: $nickname, check_already_used: $check_already_used, which: $which, check_is_allowed: $check_is_allowed);
        } catch (NicknameException) {
            return false;
        }

        return true;
    }

    /**
     * Is the given string a valid canonical nickname form?
     * @param string $nickname
     * @return bool
     */
    public static function isCanonical(string $nickname): bool
    {
        return preg_match('/^(?:' . self::CANONICAL_FMT . ')$/', $nickname) > 0;
    }

    /**
     * Is the given string in our nickname blacklist?
     * @param string $nickname
     * @return bool
     * @throws NicknameEmptyException
     * @throws NicknameInvalidException
     * @throws NicknameNotAllowedException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     */
    public static function isBlacklisted(string $nickname): bool
    {
        $reserved = Common::config('nickname', 'blacklist');
        if (empty($reserved)) {
            return false;
        }
        return in_array($nickname, array_merge($reserved, F\map($reserved, function ($n) {
            return self::normalize($n, check_already_used: false, check_is_allowed: true);
        })));
    }
}
