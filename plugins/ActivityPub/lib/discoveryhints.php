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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Evan Prodromou
 * @author    Brion Vibber
 * @author    James Walker
 * @author    Siebrand Mazeland
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

class DiscoveryHints
{
    public static function fromXRD(XML_XRD $xrd)
    {
        $hints = [];

        if (Event::handle('StartDiscoveryHintsFromXRD', [$xrd, &$hints])) {
            foreach ($xrd->links as $link) {
                switch ($link->rel) {
                case WebFingerResource_Profile::PROFILEPAGE:
                    $hints['profileurl'] = $link->href;
                    break;
                case Discovery::UPDATESFROM:
                    if (empty($link->type) || $link->type == 'application/atom+xml') {
                        $hints['feedurl'] = $link->href;
                    }
                    break;
                case Discovery::HCARD:
                case Discovery::MF2_HCARD:
                    $hints['hcard'] = $link->href;
                    break;
                default:
                    break;
                }
            }
            Event::handle('EndDiscoveryHintsFromXRD', [$xrd, &$hints]);
        }

        return $hints;
    }

    public static function fromHcardUrl($url)
    {
        $client = new HTTPClient();
        $client->setHeader('Accept', 'text/html,application/xhtml+xml');
        try {
            $response = $client->get($url);

            if (!$response->isOk()) {
                return null;
            }
        } catch (HTTP_Request2_Exception $e) {
            // Any HTTPClient error that might've been thrown
            common_log(LOG_ERR, __METHOD__ . ':'.$e->getMessage());
            return null;
        }

        return self::hcardHints(
            $response->getBody(),
            $response->getEffectiveUrl()
        );
    }

    public static function hcardHints($body, $url)
    {
        $hcard = self::_hcard($body, $url);

        if (empty($hcard)) {
            return [];
        }

        $hints = [];

        // XXX: don't copy stuff into an array and then copy it again

        if (array_key_exists('nickname', $hcard) && !empty($hcard['nickname'][0])) {
            $hints['nickname'] = $hcard['nickname'][0];
        }

        if (array_key_exists('name', $hcard) && !empty($hcard['name'][0])) {
            $hints['fullname'] = $hcard['name'][0];
        }

        if (array_key_exists('photo', $hcard) && count($hcard['photo'])) {
            $hints['avatar'] = $hcard['photo'][0];
        }

        if (array_key_exists('note', $hcard) && !empty($hcard['note'][0])) {
            $hints['bio'] = $hcard['note'][0];
        }

        if (array_key_exists('adr', $hcard) && !empty($hcard['adr'][0])) {
            $hints['location'] = $hcard['adr'][0]['value'];
        }

        if (array_key_exists('url', $hcard) && !empty($hcard['url'][0])) {
            $hints['homepage'] = $hcard['url'][0];
        }

        return $hints;
    }

    public static function _hcard($body, $url)
    {
        $mf2 = new Mf2\Parser($body, $url);
        $mf2 = $mf2->parse();

        if (empty($mf2['items'])) {
            return null;
        }

        $hcards = [];

        foreach ($mf2['items'] as $item) {
            if (!in_array('h-card', $item['type'])) {
                continue;
            }

            // We found a match, return it immediately
            if (isset($item['properties']['url']) && in_array($url, $item['properties']['url'])) {
                return $item['properties'];
            }

            // Let's keep all the hcards for later, to return one of them at least
            $hcards[] = $item['properties'];
        }

        // No match immediately for the url we expected, but there were h-cards found
        if (count($hcards) > 0) {
            return $hcards[0];
        }

        return null;
    }
}
