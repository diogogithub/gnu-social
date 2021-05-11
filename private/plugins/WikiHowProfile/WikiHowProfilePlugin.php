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
 * Plugin to pull WikiHow-style user avatars at OpenID setup time.
 * These are not currently exposed via OpenID.
 *
 * @category  Plugins
 * @package   GNUsocial
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * @category  Plugins
 * @package   WikiHowProfilePlugin
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class WikiHowProfilePlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'WikiHow avatar fetcher',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Brion Vibber',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/Sample',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Fetches avatar and other profile information for WikiHow users when setting up an account via OpenID.'));
        return true;
    }

    /**
     * Hook for OpenID user creation; we'll pull the avatar.
     *
     * @param User $user
     * @param string $canonical OpenID provider URL
     * @param array $sreg query data from provider
     */
    public function onEndOpenIDCreateNewUser($user, $canonical, $sreg)
    {
        $this->updateProfile($user, $canonical);
        return true;
    }

    /**
     * Hook for OpenID profile updating; we'll pull the avatar.
     *
     * @param User $user
     * @param string $canonical OpenID provider URL (wiki profile page)
     * @param array $sreg query data from provider
     */
    public function onEndOpenIDUpdateUser($user, $canonical, $sreg)
    {
        $this->updateProfile($user, $canonical);
        return true;
    }

    /**
     * @param User $user
     * @param string $canonical OpenID provider URL (wiki profile page)
     */
    private function updateProfile($user, $canonical)
    {
        $prefix = 'http://www.wikihow.com/User:';

        if (substr($canonical, 0, strlen($prefix)) == $prefix) {
            // Yes, it's a WikiHow user!
            $profile = $this->fetchProfile($canonical);

            if (!empty($profile['avatar'])) {
                $this->saveAvatar($user, $profile['avatar']);
            }
        }
    }

    /**
     * Given a user's WikiHow profile URL, find their avatar.
     *
     * @param string $profileUrl user page on the wiki
     *
     * @return array of data; possible members:
     *               'avatar' => full URL to avatar image
     *
     * @throws Exception on various low-level failures
     *
     * @todo pull location, web site, and about sections -- they aren't currently marked up cleanly.
     */
    private function fetchProfile($profileUrl)
    {
        $client = HTTPClient::start();
        $response = $client->get($profileUrl);
        if (!$response->isOk()) {
            // TRANS: Exception thrown when fetching a WikiHow profile page fails.
            throw new Exception(_m('WikiHow profile page fetch failed.'));
            // HTTP error response already logged.
            return false;
        }

        // Suppress warnings during HTML parsing; non-well-formed bits will
        // spew horrible warning everywhere even though it works fine.
        $old = error_reporting();
        error_reporting($old & ~E_WARNING);

        $dom = new DOMDocument();
        $ok = $dom->loadHTML($response->getBody());

        error_reporting($old);

        if (!$ok) {
            // TRANS: Exception thrown when parsing a WikiHow profile page fails.
            throw new Exception(_m('HTML parse failure during check for WikiHow avatar.'));
            return false;
        }

        $data = array();

        $avatar = $dom->getElementById('avatarULimg');
        if ($avatar) {
            $src = $avatar->getAttribute('src');

            $base = new Net_URL2($profileUrl);
            $absolute = $base->resolve($src);
            $avatarUrl = strval($absolute);

            common_log(LOG_DEBUG, "WikiHow avatar found for $profileUrl - $avatarUrl");
            $data['avatar'] = $avatarUrl;
        }

        return $data;
    }

    /**
     * Actually save the avatar we found locally.
     *
     * @param User $user
     * @param string $url to avatar URL
     * @todo merge wrapper funcs for this into common place for 1.0 core
     */
    private function saveAvatar($user, $url)
    {
        if (!common_valid_http_url($url)) {
            // TRANS: Server exception thrown when an avatar URL is invalid.
            // TRANS: %s is the invalid avatar URL.
            throw new ServerException(sprintf(_m('Invalid avatar URL %s.'), $url));
        }

        // @todo FIXME: This should be better encapsulated
        // ripped from OStatus via oauthstore.php (for old OMB client)
        $tempfile = new TemporaryFile('gs-avatarlisten');
        $img_data = HTTPClient::quickGet($url);
        // Make sure it's at least an image file. ImageFile can do the rest.
        if (getimagesizefromstring($img_data) === false) {
            return false;
        }
        fwrite($tempfile->getResource(), $img_data);
        fflush($tempfile->getResource());

        $profile = $user->getProfile();
        $id = $profile->id;
        $imagefile = new ImageFile(-1, $tempfile->getRealPath());
        $filename = Avatar::filename(
            $id,
            image_type_to_extension($imagefile->type),
            null,
            common_timestamp()
        );
        $tempfile->commit(Avatar::path($filename));
        $profile->setOriginal($filename);
    }
}
