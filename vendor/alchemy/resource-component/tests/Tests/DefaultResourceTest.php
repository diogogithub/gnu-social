<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Resource;

use Alchemy\Resource\DefaultResource;
use Alchemy\Resource\Metadata\MetadataIterator;
use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\ResourceWriter;

class DefaultResourceTest extends \PHPUnit_Framework_TestCase
{

    public function testResourceCanBeCreatedFromAUri()
    {
        $uri = ResourceUri::fromString('mock://uri');
        $resource = new DefaultResource($uri);

        $this->assertEquals($uri, $resource->getUri());
        $this->assertInstanceOf(ResourceReader::class, $resource->getReader());
        $this->assertInstanceOf(ResourceWriter::class, $resource->getWriter());
        $this->assertInstanceOf(MetadataIterator::class, $resource->getMetadata());
    }

    public function testResourceCanBeCreatedUsingAUriAndCustomReader()
    {
        $reader = $this->prophesize(ResourceReader::class)->reveal();

        $uri = ResourceUri::fromString('mock://uri');
        $resource = new DefaultResource($uri, $reader);

        $this->assertEquals($uri, $resource->getUri());
        $this->assertSame($reader, $resource->getReader());
        $this->assertInstanceOf(ResourceWriter::class, $resource->getWriter());
        $this->assertInstanceOf(MetadataIterator::class, $resource->getMetadata());
    }

    public function testResourceCanBeCreatedUsingAUriAndCustomWriter()
    {
        $writer = $this->prophesize(ResourceWriter::class)->reveal();

        $uri = ResourceUri::fromString('mock://uri');
        $resource = new DefaultResource($uri, null, $writer);

        $this->assertEquals($uri, $resource->getUri());
        $this->assertInstanceOf(ResourceReader::class, $resource->getReader());
        $this->assertSame($writer, $resource->getWriter());
        $this->assertInstanceOf(MetadataIterator::class, $resource->getMetadata());
    }
}
