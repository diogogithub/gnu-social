<?php

declare(strict_types = 1);

namespace Component\FreeNetwork\Util\WebfingerResource;

use App\Core\Event;
use App\Entity\Note;
use Component\FreeNetwork\Util\WebfingerResource;
use PharIo\Manifest\InvalidUrlException;
use XML_XRD;
use XML_XRD_Element_Link;

/**
 * WebFinger resource for Note objects
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @copyright 2013 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 *
 * @see      http://status.net/
 */
class WebfingerResourceNote extends WebfingerResource
{
    public function __construct(?Note $object = null)
    {
        // The type argument above verifies that it's our class
        parent::__construct($object);
    }

    /**
     * Update given XRD with self's data
     */
    public function updateXRD(XML_XRD $xrd)
    {
        if (Event::handle('StartWebFingerNoticeLinks', [$xrd, $this->object])) {
            if ($this->object->isLocal()) {
                $xrd->links[] = new XML_XRD_Element_Link(
                    'alternate',
                    common_local_url(
                        'ApiStatusesShow',
                        ['id'        => $this->object->id,
                            'format' => 'atom', ],
                    ),
                    'application/atom+xml',
                );

                $xrd->links[] = new XML_XRD_Element_Link(
                    'alternate',
                    common_local_url(
                        'ApiStatusesShow',
                        ['id'        => $this->object->id,
                            'format' => 'json', ],
                    ),
                    'application/json',
                );
            } else {
                try {
                    $xrd->links[] = new XML_XRD_Element_Link(
                        'alternate',
                        $this->object->getUrl(),
                        'text/html',
                    );
                } catch (InvalidUrlException $e) {
                    // don't do a fallback in webfinger
                }
            }
            Event::handle('EndWebFingerNoticeLinks', [$xrd, $this->object]);
        }
    }
}
