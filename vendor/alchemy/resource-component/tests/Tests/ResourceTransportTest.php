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

use Alchemy\Resource\Reader\StringReader;
use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceReaderResolver;
use Alchemy\Resource\ResourceTransport;
use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\ResourceWriter;
use Alchemy\Resource\ResourceWriterResolver;
use Alchemy\Resource\Writer\StringWriter;
use Prophecy\Argument;

class ResourceTransportTest extends \PHPUnit_Framework_TestCase
{

    public function testWriteReadsFromSourceAndWritesToTarget()
    {
        $reader = new StringReader('hello');
        $writer = new StringWriter();

        $resolver = new MockResolver($reader, $writer);

        $source = ResourceUri::fromString('test://mock-resource');
        $target = ResourceUri::fromString('test://mock-target');
        $transport = new ResourceTransport($resolver, $resolver, $source);

        $transport->write($target);

        $this->assertEquals('hello', $writer->getContents());
    }
}

class MockResolver implements ResourceReaderResolver, ResourceWriterResolver
{

    private $reader;

    private $writer;

    public function __construct(ResourceReader $reader, ResourceWriter $writer)
    {
        $this->reader = $reader;
        $this->writer = $writer;
    }

    public function resolveReader(ResourceUri $resource)
    {
        return $this->reader;
    }

    public function resolveWriter(ResourceUri $resource)
    {
        return $this->writer;
    }
}
