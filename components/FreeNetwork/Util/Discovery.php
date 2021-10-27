<?php

declare(strict_types = 1);
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * This class performs lookups based on methods implemented in separate
 * classes, where a resource uri is given. Examples are WebFinger (RFC7033)
 * and the LRDD (Link-based Resource Descriptor Discovery) in RFC6415.
 *
 * PHP version 5
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Discovery
 * @package   GNUsocial
 *
 * @author    James Walker <james@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2010 StatusNet, Inc.
 * @copyright 2013 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 *
 * @see      http://www.gnu.org/software/social/
 */

namespace Component\FreeNetwork\Util;

use App\Core\Event;
use App\Core\GSFile;
use App\Core\HTTPClient;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Util\Exception\ClientException;
use Exception;
use XML_XRD;
use XML_XRD_Element_Link;

class Discovery
{
    public const LRDD_REL    = 'lrdd';
    public const UPDATESFROM = 'http://schemas.google.com/g/2010#updates-from';
    public const HCARD       = 'http://microformats.org/profile/hcard';
    public const MF2_HCARD   = 'http://microformats.org/profile/h-card';   // microformats2 h-card

    public const JRD_MIMETYPE_OLD = 'application/json';    // RFC6415 uses this
    public const JRD_MIMETYPE     = 'application/jrd+json';
    public const XRD_MIMETYPE     = 'application/xrd+xml';

    public array $methods = [];

    /**
     * Constructor for a discovery object
     *
     * Registers different discovery methods.
     */
    public function __construct()
    {
        if (Event::handle('StartDiscoveryMethodRegistration', [$this])) {
            Event::handle('EndDiscoveryMethodRegistration', [$this]);
        }
    }

    public static function supportedMimeTypes(): array
    {
        return [
            'json'    => self::JRD_MIMETYPE,
            'jsonold' => self::JRD_MIMETYPE_OLD,
            'xml'     => self::XRD_MIMETYPE,
        ];
    }

    /**
     * Register a discovery class
     *
     * @param string $class Class name
     */
    public function registerMethod($class): void
    {
        $this->methods[] = $class;
    }

    /**
     * Given a user ID, return the first available resource descriptor
     *
     * @param string $id User ID URI
     *
     * @return XML_XRD object for the resource descriptor of the id
     */
    public function lookup(string $id): XML_XRD
    {
        // Normalize the incoming $id to make sure we have an uri
        $uri = self::normalize($id);

        Log::debug(sprintf('Performing discovery for "%s" (normalized "%s")', $id, $uri));

        foreach ($this->methods as $class) {
            try {
                $xrd = new XML_XRD();

                Log::debug("LRDD discovery method for '{$uri}': {$class}");
                $lrdd  = new $class;
                $links = $lrdd->discover($uri);
                $link  = self::getService($links, self::LRDD_REL);

                // Load the LRDD XRD
                if (!empty($link->template)) {
                    $xrd_uri = self::applyTemplate($link->template, $uri);
                } elseif (!empty($link->href)) {
                    $xrd_uri = $link->href;
                } else {
                    throw new Exception('No resource descriptor URI in link.');
                }

                $headers = [];
                if (!\is_null($link->type)) {
                    $headers['Accept'] = $link->type;
                }

                $response = HTTPClient::get($xrd_uri, ['headers' => $headers]);
                if ($response->getStatusCode() !== 200) {
                    throw new Exception('Unexpected HTTP status code.');
                }

                switch (GSFile::mimetypeBare($response->getHeaders()['content-type'][0])) {
                    case self::JRD_MIMETYPE_OLD:
                    case self::JRD_MIMETYPE:
                        $type = 'json';
                        break;
                    case self::XRD_MIMETYPE:
                        $type = 'xml';
                        break;
                    default:
                        // fall back to letting XML_XRD auto-detect
                        Log::debug('No recognized content-type header for resource descriptor body on ' . $xrd_uri);
                        $type = null;
                }
                $xrd->loadString($response->getContent(), $type);
                return $xrd;
            } catch (ClientException $e) {
                if ($e->getCode() === 403) {
                    Log::info(sprintf('%s: Aborting discovery on URL %s: %s', $class, $uri, $e->getMessage()));
                    break;
                }
            } catch (Exception $e) {
                Log::info(sprintf('%s: Failed for %s: %s', $class, $uri, $e->getMessage()));
                continue;
            }
        }

        // TRANS: Exception. %s is an ID.
        throw new Exception(sprintf(_m('Unable to find services for %s.'), $id));
    }

    /**
     * Given an array of links, returns the matching service
     *
     * @param array  $links   Links to check (as instances of XML_XRD_Element_Link)
     * @param string $service Service to find
     *
     * @return XML_XRD_Element_Link $link
     */
    public static function getService(array $links, $service): XML_XRD_Element_Link
    {
        foreach ($links as $link) {
            if ($link->rel === $service) {
                return $link;
            }
            Log::debug('LINK: rel ' . $link->rel . ' !== ' . $service);
        }

        throw new Exception('No service link found');
    }

    /**
     * Given a "user id" make sure it's normalized to an acct: uri
     *
     * @param string $uri User ID to normalize
     *
     * @return string normalized acct: URI
     */
    public static function normalize(string $uri): string
    {
        $parts = parse_url($uri);
        // If we don't have a scheme, but the path implies user@host,
        // though this is far from a perfect matching procedure...
        if (!isset($parts['scheme']) && isset($parts['path'])
            && preg_match('/[\w@\w]/u', $parts['path'])) {
            return 'acct:' . $uri;
        }

        return $uri;
    }

    public static function isAcct($uri): bool
    {
        return mb_strtolower(mb_substr($uri, 0, 5)) == 'acct:';
    }

    /**
     * Apply a template using an ID
     *
     * Replaces {uri} in template string with the ID given.
     *
     * @param string $template Template to match
     * @param string $uri      URI to replace with
     *
     * @return string replaced values
     */
    public static function applyTemplate($template, $uri): string
    {
        return str_replace('{uri}', urlencode($uri), $template);
    }
}
