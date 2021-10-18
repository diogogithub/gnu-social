<?php

namespace Component\FreeNetwork\Util\LrddMethod;

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
use App\Core\Log;
use Component\FreeNetwork\Util\LrddMethod;
use Exception;

/**
 * Implementation of discovery using host-meta file
 *
 * Discovers resource descriptor file for a user by going to the
 * organization's host-meta file and trying to find a template for LRDD.
 *
 * @category  Discovery
 * @package   GNUsocial
 *
 * @author    James Walker <james@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class LrddMethodHostMeta extends LRDDMethod
{
    /**
     * For RFC6415 and HTTP URIs, fetch the host-meta file
     * and look for LRDD templates
     *
     * @param mixed $uri
     */
    public function discover($uri)
    {
        // This is allowed for RFC6415 but not the 'WebFinger' RFC7033.
        $try_schemes = ['https', 'http'];

        $scheme = mb_strtolower(parse_url($uri, PHP_URL_SCHEME));
        switch ($scheme) {
            case 'acct':
                // We can't use parse_url data for this, since the 'host'
                // entry is only set if the scheme has '://' after it.
                $parts = explode('@', parse_url($uri, PHP_URL_PATH), 2);

                if (!Discovery::isAcct($uri) || count($parts) != 2) {
                    throw new Exception('Bad resource URI: ' . $uri);
                }
                [, $domain] = $parts;
                break;
            case 'http':
            case 'https':
                $domain      = mb_strtolower(parse_url($uri, PHP_URL_HOST));
                $try_schemes = [$scheme];
                break;
            default:
                throw new Exception('Unable to discover resource descriptor endpoint.');
        }

        foreach ($try_schemes as $scheme) {
            $url = $scheme . '://' . $domain . '/.well-known/host-meta';

            try {
                $response = self::fetchUrl($url);
                $this->xrd->loadString($response->getBody());
            } catch (Exception $e) {
                Log::debug('LRDD could not load resource descriptor: ' . $url . ' (' . $e->getMessage() . ')');
                continue;
            }
            return $this->xrd->links;
        }

        throw new Exception('Unable to retrieve resource descriptor links.');
    }
}
