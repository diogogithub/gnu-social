<?php

declare(strict_types = 1);

namespace Component\FreeNetwork\Util;

use App\Core\Event;
use App\Core\HTTPClient;
use Exception;
use Symfony\Contracts\HttpClient\ResponseInterface;
use XML_XRD;

/**
 * Abstract class for LRDD discovery methods
 *
 * Objects that extend this class can retrieve an array of
 * resource descriptor links for the URI. The array consists
 * of XML_XRD_Element_Link elements.
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
abstract class LrddMethod
{
    protected $xrd;

    public function __construct()
    {
        $this->xrd = new XML_XRD();
    }

    /**
     * Discover interesting info about the URI
     *
     * @param string $uri URI to inquire about
     *
     * @return array of XML_XRD_Element_Link elements to discovered resource descriptors
     */
    abstract public function discover($uri);

    protected function fetchUrl($url, $method = 'get'): ResponseInterface
    {
        // If we have a blacklist enabled, let's check against it
        Event::handle('UrlBlacklistTest', [$url]);

        $response = HTTPClient::$method($url);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('Unexpected HTTP status code.');
        }

        return $response;
    }
}
