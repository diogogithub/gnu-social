<?php

namespace Component\FreeNetwork\Util;

use App\Core\Entity;
use App\Util\Common;
use App\Util\Exception\ServerException;
use XML_XRD;

/**
 * WebFinger resource parent class
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @copyright 2013 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 *
 * @see      http://status.net/
 */
abstract class WebfingerResource
{
    protected $identities = [];

    protected $object;
    protected $type;

    public function __construct(Entity $object)
    {
        $this->object = $object;
    }

    public function getObject()
    {
        if ($this->object === null) {
            throw new ServerException('Object is not set');
        }
        return $this->object;
    }

    /**
     * List of alternative IDs of a certain Actor
     *
     * @return array
     */
    public function getAliases(): array
    {
        $aliases = $this->object->getAliasesWithIDs();

        // Some sites have changed from http to https and still want
        // (because remote sites look for it) verify that they are still
        // the same identity as they were on HTTP. Should NOT be used if
        // you've run HTTPS all the time!
        if (Common::config('fix', 'legacy_http')) {
            foreach ($aliases as $alias => $id) {
                if (!strtolower(parse_url($alias, PHP_URL_SCHEME)) === 'https') {
                    continue;
                }
                $aliases[preg_replace('/^https:/i', 'http:', $alias, 1)] = $id;
            }
        }

        // return a unique set of aliases by extracting only the keys
        return array_keys($aliases);
    }

    abstract public function updateXRD(XML_XRD $xrd);
}
