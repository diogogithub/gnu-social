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
use Alchemy\Resource\ResourceReaderFactory;
use Alchemy\Resource\ResourceUri;

class StreamReaderFactory implements ResourceReaderFactory
{

    /**
     * @param ResourceUri $resource
     * @return ResourceReader
     */
    public function createReaderFor(ResourceUri $resource)
    {
        return new StreamReader($resource);
    }
}
