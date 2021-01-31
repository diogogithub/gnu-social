<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource;

use Alchemy\Resource\Metadata\MetadataIterator;
use Alchemy\Resource\Metadata\NullMetadataIterator;
use Alchemy\Resource\Reader\NullReader;
use Alchemy\Resource\Writer\NullWriter;

class DefaultResource implements Resource
{
    /**
     * @var ResourceUri
     */
    private $uri;

    /**
     * @var ResourceReader
     */
    private $reader;

    /**
     * @var ResourceWriter
     */
    private $writer;

    /**
     * @var MetadataIterator
     */
    private $metadataIterator;

    public function __construct(
        ResourceUri $resourceUri,
        ResourceReader $reader = null,
        ResourceWriter $writer = null,
        MetadataIterator $metadataIterator = null
    ) {
        $this->uri = $resourceUri;
        $this->reader = $reader ?: new NullReader();
        $this->writer = $writer ?: new NullWriter();
        $this->metadataIterator = $metadataIterator ?: new NullMetadataIterator();
    }

    /**
     * @return ResourceUri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return ResourceReader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @return ResourceWriter
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * @return MetadataIterator
     */
    public function getMetadata()
    {
        return $this->metadataIterator;
    }
}
