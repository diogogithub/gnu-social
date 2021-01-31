<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Metadata;

use Alchemy\Resource\Reader\NullReader;
use Alchemy\Resource\Resource;
use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\ResourceWriter;
use Alchemy\Resource\Writer\NullWriter;

final class Metadata implements Resource
{
    /**
     * @var MetadataIterator
     */
    private $metadataIterator;

    /**
     * @var ResourceUri
     */
    private $uri;

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param ResourceUri $uri
     * @param MetadataIterator $metadataIterator
     * @param string $name
     * @param mixed $value
     */
    public function __construct(ResourceUri $uri, MetadataIterator $metadataIterator, $name, $value)
    {
        $this->uri = $uri;
        $this->metadataIterator = $metadataIterator;
        $this->name = (string) $name;
        $this->value = $value;
    }

    /**
     * @return ResourceUri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return ResourceReader
     */
    public function getReader()
    {
        return new NullReader();
    }

    /**
     * @return ResourceWriter
     */
    public function getWriter()
    {
        return new NullWriter();
    }

    /**
     * @return MetadataIterator
     */
    public function getMetadata()
    {
        return $this->metadataIterator;
    }
}
