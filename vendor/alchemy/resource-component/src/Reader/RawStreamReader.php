<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Reader;

use Alchemy\Resource\ResourceReader;

class RawStreamReader implements ResourceReader
{

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param resource $resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        $this->stream = $resource;
    }

    /**
     * @return resource
     */
    public function getContentsAsStream()
    {
        return $this->stream;
    }
}
