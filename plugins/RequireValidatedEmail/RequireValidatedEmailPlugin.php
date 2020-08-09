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
 * Plugin that requires the user to have a validated email address before they
 * can post notices
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Brion Vibber <brion@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2011 StatusNet Inc. http://status.net/
 * @copyright 2009-2013 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Plugin for requiring a validated email before posting.
 *
 * Enable this plugin using addPlugin('RequireValidatedEmail');
 * @copyright 2009-2013 Free Software Foundation, Inc http://www.fsf.org
 * @copyright 2009-2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RequireValidatedEmailPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    /**
     * Users created before this date will be exempted
     * without the validation requirement.
     */
    public $exemptBefore = null;
    // Alternative more obscure term for exemption dates
    public $grandfatherCutoff = null;

    /**
     * If OpenID plugin is installed, users with a verified OpenID
     * association whose provider URL matches one of these regexes
     * will be considered to be sufficiently valid for our needs.
     *
     * For example, to trust WikiHow and Wikipedia OpenID users:
     *
     * addPlugin('RequireValidatedEmailPlugin', [
     *    'trustedOpenIDs' => [
     *        '!^https?://\w+\.wikihow\.com/!',
     *        '!^https?://\w+\.wikipedia\.org/!',
     *    ],
     * ]);
     */
    public $trustedOpenIDs = [];

    /**
     * Whether or not to disallow login for unvalidated users.
     */
    public $disallowLogin = false;

    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect(
            'main/confirmfirst/:code',
            ['action' => 'confirmfirstemail']
        );
        return true;
    }

    /**
     * Event handler for notice saves; rejects the notice
     * if user's address isn't validated.
     *
     * @param Notice $notice The notice being saved
     *
     * @return bool hook result code
     */
    public function onStartNoticeSave(Notice $notice)
    {
        $author = $notice->getProfile();
        if (!$author->isLocal()) {
            // remote notice
            return true;
        }
        $user = $author->getUser();

        if ($user !== common_current_user()) {
            // Not the current user, must be legitimate (like welcomeuser)
            return true;
        }

        if (!$this->validated($user)) {
            // TRANS: Client exception thrown when trying to post notices before validating an e-mail address.
            $msg = _m('You must validate your email address before posting.');
            throw new ClientException($msg);
        }
        return true;
    }

    /**
     * Event handler for registration attempts; rejects the registration
     * if email field is missing.
     *
     * @param Action $action Action being executed
     *
     * @return bool hook result code
     */
    public function onStartRegisterUser(&$user, &$profile)
    {
        $email = $user->email;

        if (empty($email)) {
            // TRANS: Client exception thrown when trying to register without providing an e-mail address.
            throw new ClientException(_m('You must provide an email address to register.'));
        }

        return true;
    }

    /**
     * Check if a user has a validated email address or was
     * otherwise exempted.
     *
     * @param User $user User to valide
     *
     * @return bool
     */
    protected function validated(User $user): bool
    {
        // The email field is only stored after validation...
        // Until then you'll find them in confirm_address.
        $knownGood = (
            !empty($user->email)
            || $this->exempted($user)
            || $this->hasTrustedOpenID($user)
        );

        // Give other plugins a chance to override, if they can validate
        // that somebody's ok despite a non-validated email.

        // @todo FIXME: This isn't how to do it! Use Start*/End* instead
        Event::handle(
            'RequireValidatedEmailPlugin_Override',
            [$user, &$knownGood]
        );

        return $knownGood;
    }

    /**
     * Check if a user was created before the exemption date.
     * If so, we won't need to check for validation.
     *
     * @param User $user User to check
     *
     * @return bool true if user is exempted
     */
    protected function exempted(User $user): bool
    {
        $exempt_before = ($this->exemptBefore ?? $this->grandfatherCutoff);

        if (!empty($exempt_before)) {
            $utc_timezone = new DateTimeZone('UTC');
            $created_date = new DateTime($user->created, $utc_timezone);
            $exempt_date  = new DateTime($exempt_before, $utc_timezone);
            if ($created_date < $exempt_date) {
                return true;
            }
        }
        return false;
    }

    /**
     * Override for RequireValidatedEmail plugin. If we have a user who's
     * not validated an e-mail, but did come from a trusted provider,
     * we'll consider them ok.
     *
     * @param User $user User to check
     *
     * @return bool true if user has a trusted OpenID.
     */
    public function hasTrustedOpenID(User $user)
    {
        if ($this->trustedOpenIDs && class_exists('User_openid')) {
            foreach ($this->trustedOpenIDs as $regex) {
                $oid = new User_openid();

                $oid->user_id = $user->id;

                $oid->find();
                while ($oid->fetch()) {
                    if (preg_match($regex, $oid->canonical)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Add version information for this plugin.
     *
     * @param array &$versions Array of associative arrays of version data
     *
     * @return boolean hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] =
          array('name' => 'Require Validated Email',
                'version' => self::PLUGIN_VERSION,
                'author' => 'Craig Andrews, '.
                'Evan Prodromou, '.
                'Brion Vibber',
                'homepage' =>
                GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/RequireValidatedEmail',
                'rawdescription' =>
                // TRANS: Plugin description.
                _m('Disables posting without a validated email address.'));

        return true;
    }

    /**
     * Show an error message about validating user email before posting
     *
     * @param string $tag    Current tab tag value
     * @param Action $action action being shown
     * @param Form   $form   object producing the form
     *
     * @return boolean hook value
     */
    public function onStartMakeEntryForm($tag, $action, &$form)
    {
        $user = common_current_user();
        if (!empty($user)) {
            if (!$this->validated($user)) {
                $action->element('div', array('class'=>'error'), _m('You must validate an email address before posting!'));
            }
        }
        return true;
    }

    /**
     * Prevent unvalidated folks from creating spam groups.
     *
     * @param Profile $profile User profile we're checking
     * @param string $right rights key
     * @param boolean $result if overriding, set to true/false has right
     * @return boolean hook result value
     */
    public function onUserRightsCheck(Profile $profile, $right, &$result)
    {
        if ($right == Right::CREATEGROUP ||
            ($this->disallowLogin && ($right == Right::WEBLOGIN || $right == Right::API))) {
            $user = User::getKV('id', $profile->id);
            if ($user && !$this->validated($user)) {
                $result = false;
                return false;
            }
        }
        return true;
    }

    public function onLoginAction($action, &$login)
    {
        if ($action == 'confirmfirstemail') {
            $login = true;
            return false;
        }
        return true;
    }
}
