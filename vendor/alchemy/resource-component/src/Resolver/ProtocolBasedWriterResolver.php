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

use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\ResourceWriter;
use Alchemy\Resource\ResourceWriterResolver;

class ProtocolBasedWriterResolver implements ResourceWriterResolver
{
    /**
     * @var ResourceWriter[]
     */
    private $writers = [];

    /**
     * @var int[] Dictionary of writer indexes, indexed by resource protocol name
     */
    private $protocolWriterIndexes = [];

    /**
     * @param ResourceWriter $writer
     * @param string|string[] $protocols List of compatible protocols
     */
    public function addWriter(ResourceWriter $writer, $protocols)
    {
        $protocols = is_array($protocols) ? $protocols : [ $protocols ];
        $index = count($this->writers);

        $this->writers[$index] = $writer;

        foreach ($protocols as $protocol) {
            $this->protocolWriterIndexes[$protocol] = $index;
        }
    }

    /**
     * Resolves a writer for the given resource URI.
     *
     * @param ResourceUri $resource
     * @return ResourceWriter
     */
    public function resolveWriter(ResourceUri $resource)
    {
        if (! array_key_exists($resource->getProtocol(), $this->protocolWriterIndexes)) {
            throw new \RuntimeException('Unsupported protocol: ' . $resource->getProtocol() . '( ' . $resource  . ')');
        }

        /** @var int $index */
        $index = $this->protocolWriterIndexes[$resource->getProtocol()];

        return $this->writers[$index];
    }
}
