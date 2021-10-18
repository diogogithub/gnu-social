<?php

namespace Component\FreeNetwork\Util\LrddMethod;

use App\Core\HTTPClient;
use App\Core\Log;
use Component\FreeNetwork\Util\LinkHeader;
use Component\FreeNetwork\Util\LrddMethod;
use Exception;
use XML_XRD_Element_Link;

/**
 * Implementation of discovery using HTTP Link header
 *
 * Discovers XRD file for a user by fetching the URL and reading any
 * Link: headers in the HTTP response.
 *
 * @category  Discovery
 * @package   StatusNet
 *
 * @author    James Walker <james@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 *
 * @see      http://status.net/
 */
class LrddMethodLinkHeader extends LRDDMethod
{
    /**
     * For HTTP IDs fetch the URL and look for Link headers.
     *
     * @param mixed $uri
     *
     * @todo fail out of WebFinger URIs faster
     *
     */
    public function discover($uri)
    {
        $response = self::fetchUrl($uri, HTTPClient::METHOD_HEAD);

        $link_header = $response->getHeader('Link');
        if (empty($link_header)) {
            throw new Exception('No Link header found');
        }
        Log::debug('LRDD LinkHeader found: ' . var_export($link_header, true));

        return self::parseHeader($link_header);
    }

    /**
     * Given a string or array of headers, returns JRD-like assoc array
     *
     * @param array|string $header string or array of strings for headers
     *
     * @return array of associative arrays in JRD-like array format
     */
    protected static function parseHeader($header)
    {
        $lh = new LinkHeader($header);

        $link = new XML_XRD_Element_Link($lh->rel, $lh->href, $lh->type);

        return [$link];
    }
}
