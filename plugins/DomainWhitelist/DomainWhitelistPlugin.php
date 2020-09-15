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
 * Restrict the email addresses in a domain to a select whitelist
 *
 * @category  Cache
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Restrict the email addresses to a domain whitelist
 *
 * @category  General
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DomainWhitelistPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    /**
     * Get the path to the plugin's installation directory. Used
     * to link in js files and whatnot.
     *
     * @return String the absolute path
     */
    protected function getPath()
    {
        return preg_replace('/^' . preg_quote(INSTALLDIR, '/') . '\//', '', dirname(__FILE__));
    }

    /**
     * Link in a JavaScript script for the whitelist invite form
     *
     * @param Action $action Action being shown
     *
     * @return boolean hook flag
     */
    public function onEndShowStatusNetScripts($action)
    {
        $name = $action->arg('action');
        if ($name == 'invite') {
            $action->script($this->getPath() . '/js/whitelistinvite.js');
        }
        return true;
    }

    public function onRequireValidatedEmailPlugin_Override($user, &$knownGood)
    {
        $knownGood = (!empty($user->email) && $this->matchesWhitelist($user->email));
        return true;
    }

    public function onEndValidateUserEmail($user, $email, &$valid)
    {
        if ($valid) { // it's otherwise valid
            if (!$this->matchesWhitelist($email)) {
                $whitelist = $this->getWhitelist();
                if (count($whitelist) == 1) {
                    // TRANS: Client exception thrown when a given e-mailaddress is not in the domain whitelist.
                    // TRANS: %s is a whitelisted e-mail domain.
                    $message = sprintf(
                        _m('Email address must be in this domain: %s.'),
                        $whitelist[0]
                    );
                } else {
                    // TRANS: Client exception thrown when a given e-mailaddress is not in the domain whitelist.
                    // TRANS: %s are whitelisted e-mail domains separated by comma's (localisable).
                    $message = sprintf(
                        _m('Email address must be in one of these domains: %s.'),
                        // TRANS: Separator for whitelisted domains.
                        implode(_m('SEPARATOR', ', '), $whitelist)
                    );
                }
                throw new ClientException($message);
            }
        }
        return true;
    }

    public function onStartAddEmailAddress($user, $email)
    {
        if (!$this->matchesWhitelist($email)) {
            // TRANS: Exception thrown when an e-mail address does not match the site's domain whitelist.
            throw new Exception(_m('That email address is not allowed on this site.'));
        }

        return true;
    }

    public function onEndValidateEmailInvite($user, $email, &$valid)
    {
        if ($valid) {
            $valid = $this->matchesWhitelist($email);
        }

        return true;
    }

    public function matchesWhitelist($email)
    {
        $whitelist = $this->getWhitelist();

        if (empty($whitelist) || empty($whitelist[0])) {
            return true;
        }

        $userDomain = $this->domainFromEmail($email);

        return in_array($userDomain, $whitelist);
    }

    /**
     * Helper function to pull out a domain from
     * an email address
     *
     * @param string $email and email address
     * @return string the domain
     */
    public function domainFromEmail($email)
    {
        $parts = explode('@', $email);
        return strtolower(trim($parts[1]));
    }

    public function getWhitelist()
    {
        $whitelist = common_config('email', 'whitelist');

        if (is_array($whitelist)) {
            return $this->sortWhiteList($whitelist);
        } else {
            return explode('|', $whitelist);
        }
    }

    /**
     * This is a filter function passed in to array_filter()
     * in order to strip out the user's domain, which will
     * be re-inserted as the first element (see sortWhitelist()
     * below).
     *
     * @param string $domain domain to check
     * @return boolean whether to include the domain
     */
    public function userDomainFilter($domain)
    {
        $user       = common_current_user();
        $userDomain = $this->domainFromEmail($user->email);
        if ($userDomain == $domain) {
            return false;
        }
        return true;
    }

    /**
     * This function sorts the whitelist alphabetically, and sets the
     * current user's domain as the first element in the array of
     * allowed domains. Mostly, this is for the JavaScript on the invite
     * page--in the case of multiple allowed domains, it's nicer if the
     * user's own domain is the first option, and this seemed like a good
     * way to do it.
     *
     * @param array $whitelist whitelist of allowed email domains
     * @return array an ordered or sorted version of the whitelist
     */
    public function sortWhitelist($whitelist)
    {
        $whitelist = array_unique($whitelist);
        natcasesort($whitelist);

        $user = common_current_user();

        if (!empty($user) && !empty($user->email)) {
            $userDomain = $this->domainFromEmail($user->email);

            $orderedWhitelist = array_values(
                array_filter(
                    $whitelist,
                    array($this, "userDomainFilter")
                )
            );

            if (in_array($userDomain, $whitelist)) {
                array_unshift($orderedWhitelist, $userDomain);
            }
            return $orderedWhitelist;
        }

        return $whitelist;
    }

    /**
     * Show a fancier invite form when domains are restricted to the
     * whitelist.
     *
     * @param action $action the invite action
     * @return boolean hook value
     */
    public function onStartShowInviteForm($action)
    {
        $this->showConfirmDialog($action);
        $form = new WhitelistInviteForm($action, $this->getWhitelist());
        $form->show();
        return false;
    }

    public function showConfirmDialog($action)
    {
        // For JQuery UI modal dialog
        $action->elementStart(
            'div',
            // TRANS: Title for invitiation deletion dialog.
            array('id' => 'confirm-dialog', 'title' => _m('Confirmation Required'))
        );
        // TRANS: Confirmation text for invitation deletion dialog.
        $action->text(_m('Really delete this invitation?'));
        $action->elementEnd('div');
    }

    /**
     * This is a bit of a hack. We take the values from the custom
     * whitelist invite form and reformat them so they look like
     * their coming from the the normal invite form.
     *
     * @param action &$action the invite action
     * @return boolean hook value
     */
    public function onStartSendInvitations(&$action)
    {
        $emails    = [];
        $usernames = $action->arg('username');
        $domains   = $action->arg('domain');

        foreach ($usernames as $key => $username) {
            if (!empty($username)) {
                $emails[] = $username . '@' . $domains[$key] . "\n";
            }
        }

        $action->args['addresses'] = implode('', $emails);

        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'DomainWhitelist',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Evan Prodromou, Zach Copley',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/DomainWhitelist',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Restrict domains for email users.'));
        return true;
    }
}
