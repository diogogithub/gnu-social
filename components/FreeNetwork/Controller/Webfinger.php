<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
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
 */

namespace Component\FreeNetwork\Controller;

use App\Core\Event;
use function App\Core\I18n\_m;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchActorException;
use Component\FreeNetwork\Util\Discovery;
use Component\FreeNetwork\Util\WebfingerResource;
use Component\FreeNetwork\Util\XrdController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package WebFingerPlugin
 *
 * @author James Walker <james@status.net>
 * @author Mikael Nordfeldth <mmn@hethane.se>
 * @author Diogo Peralta Cordeiro
 */
class Webfinger extends XrdController
{
    protected $resource; // string with the resource URI
    protected $target; // object of the WebFingerResource class

    public function handle(Request $request): array
    {
        // throws exception if resource is empty
        $this->resource = Discovery::normalize($this->string('resource'));

        try {
            if (Event::handle('StartGetWebFingerResource', [$this->resource, &$this->target, $this->params()])) {
                Event::handle('EndGetWebFingerResource', [$this->resource, &$this->target, $this->params()]);
            }
        } catch (NoSuchActorException $e) {
            throw new ClientException($e->getMessage(), 404);
        }

        if (!$this->target instanceof WebfingerResource) {
            // TRANS: Error message when an object URI which we cannot find was requested
            throw new ClientException(_m('Resource not found in local database.'), 404);
        }

        return parent::handle($request);
    }

    protected function setXRD()
    {
        $this->xrd->subject = $this->resource;

        foreach ($this->target->getAliases() as $alias) {
            if ($alias != $this->xrd->subject && !in_array($alias, $this->xrd->aliases)) {
                $this->xrd->aliases[] = $alias;
            }
        }

        $this->target->updateXRD($this->xrd);
    }
}
