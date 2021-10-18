<?php

namespace Component\FreeNetwork\Util\WebfingerResource;

use App\Core\Event;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Util\Common;
use Component\FreeNetwork\Exception\WebfingerReconstructionException;
use Component\FreeNetwork\Util\WebfingerResource;
use XML_XRD;
use XML_XRD_Element_Link;

/**
 * WebFinger resource for Profile objects
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @author    Diogo Peralta Cordeiro
 * @copyright 2013, 2021 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 */
class WebfingerResourceActor extends WebFingerResource
{
    const PROFILEPAGE = 'http://webfinger.net/rel/profile-page';

    public function __construct(Actor $object = null)
    {
        // The type argument above verifies that it's our class
        parent::__construct($object);
    }

    public function getAliases()
    {
        $aliases = [];

        try {
            // Try to create an acct: URI if we're dealing with a profile
            $aliases[] = $this->reconstructAcct();
        } catch (WebFingerReconstructionException $e) {
            Log::debug("WebFinger reconstruction for Profile failed (id={$this->object->getID()})");
        }

        return array_merge($aliases, parent::getAliases());
    }

    public function reconstructAcct()
    {
        $acct = null;

        if (Event::handle('StartWebFingerReconstruction', [$this->object, &$acct])) {
            // TODO: getUri may not always give us the correct host on remote users?
            $host = parse_url($this->object->getUri(Router::ABSOLUTE_URL), PHP_URL_HOST);
            if (empty($this->object->getNickname()) || empty($host)) {
                throw new WebFingerReconstructionException(print_r($this->object, true));
            }
            $acct = mb_strtolower(sprintf('acct:%s@%s', $this->object->getNickname(), $host));

            Event::handle('EndWebFingerReconstruction', [$this->object, &$acct]);
        }

        return $acct;
    }

    public function updateXRD(XML_XRD $xrd)
    {
        if (Event::handle('StartWebFingerProfileLinks', [$xrd, $this->object])) {

            // Profile page, can give more metadata from Link header or HTML parsing
            $xrd->links[] = new XML_XRD_Element_Link(self::PROFILEPAGE,
                $this->object->getUrl(Router::ABSOLUTE_URL), 'text/html');

//            // XFN
//            $xrd->links[] = new XML_XRD_Element_Link('http://gmpg.org/xfn/11',
//                $this->object->getUrl(), 'text/html');
//            if ($this->object->isPerson()) {
//                // FOAF for user
//                $xrd->links[] = new XML_XRD_Element_Link('describedby',
//                    common_local_url('foaf',
//                        ['nickname' => $this->object->getNickname()]),
//                    'application/rdf+xml');
//
//                // nickname discovery for apps etc.
//                $link = new XML_XRD_Element_Link('http://apinamespace.org/atom',
//                    common_local_url('ApiAtomService',
//                        ['id' => $this->object->getNickname()]),
//                    'application/atomsvc+xml');
//                // XML_XRD must implement changing properties first           $link['http://apinamespace.org/atom/username'] = $this->object->getNickname();
//                $xrd->links[] = clone $link;
//
//                $link = new XML_XRD_Element_Link('http://apinamespace.org/twitter', $apiRoot);
//                // XML_XRD must implement changing properties first            $link['http://apinamespace.org/twitter/username'] = $this->object->getNickname();
//                $xrd->links[] = clone $link;
//            } elseif ($this->object->isGroup()) {
//                // FOAF for group
//                $xrd->links[] = new XML_XRD_Element_Link('describedby',
//                    common_local_url('foafgroup',
//                        ['nickname' => $this->object->getNickname()]),
//                    'application/rdf+xml');
//            }

            Event::handle('EndWebFingerProfileLinks', [$xrd, $this->object]);
        }
    }
}
