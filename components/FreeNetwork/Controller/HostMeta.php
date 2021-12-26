<?php

declare(strict_types = 1);

/**
 * @author   James Walker <james@status.net>
 * @author   Craig Andrews <candrews@integralblue.com>
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 */

namespace Component\FreeNetwork\Controller;

use App\Core\Event;
use Component\FreeNetwork\Util\Discovery;
use Component\FreeNetwork\Util\XrdController;

class HostMeta extends XrdController
{
    protected string $default_mimetype = Discovery::XRD_MIMETYPE;

    public function setXRD()
    {
        if (Event::handle('StartHostMetaLinks', [&$this->xrd->links])) {
            Event::handle('EndHostMetaLinks', [&$this->xrd->links]);
        }
    }
}
