<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Resolver;

use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceReaderFactory;
use Alchemy\Resource\ResourceReaderResolver;
use Alchemy\Resource\ResourceUri;

class ProtocolBasedReaderResolver implements ResourceReaderResolver
{
    /**
     * @var ResourceReaderFactory[]
     */
    private $factories = [];

    /**
     * @var int[] Dictionary of factory indexes, indexed by resource protocol name
     */
    private $protocolFactoryIndexes = [];

    /**
     * @param ResourceReaderFactory $factory
     * @param string|string[] $protocols List of compatible protocols
     */
    public function addFactory(ResourceReaderFactory $factory, $protocols)
    {
        $protocols = is_array($protocols) ? $protocols : [ $protocols ];
        $index = count($this->factories);

        $this->factories[$index] = $factory;

        foreach ($protocols as $protocol) {
            $this->protocolFactoryIndexes[$protocol] = (int) $index;
        }
    }

    /**
     * Resolves a reader for the given resource URI.
     *
     * @param ResourceUri $resource
     * @return ResourceReader
     */
    public function resolveReader(ResourceUri $resource)
    {
        if (! array_key_exists($resource->getProtocol(), $this->protocolFactoryIndexes)) {
            throw new \RuntimeException('Unsupported protocol: ' . $resource->getProtocol() . '( ' . $resource  . ')');
        }

        /** @var int $factoryIndex */
        $factoryIndex = $this->protocolFactoryIndexes[$resource->getProtocol()];
        /** @var ResourceReaderFactory $factory */
        $factory = $this->factories[$factoryIndex];

        return $factory->createReaderFor($resource);
    }
}
