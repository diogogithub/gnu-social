<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Resolver;

use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceReaderFactory;
use Alchemy\Resource\Resolver\ProtocolBasedReaderResolver;
use Alchemy\Resource\ResourceUri;

class ProtocolBasedReaderResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testResolverTriggersAnErrorForUnsupportedProtocols()
    {
        $this->setExpectedException(\RuntimeException::class);

        $resolver = new ProtocolBasedReaderResolver();
        $resource = new ResourceUri('file://tests/œarchives/test.zip');

        $resolver->resolveReader($resource);
    }

    public function testResolverResolvesWriterForRegisteredProtocols()
    {
        $resolver = new ProtocolBasedReaderResolver();

        $fileReader = $this->prophesize(ResourceReader::class)->reveal();
        $zipReader = $this->prophesize(ResourceReader::class)->reveal();

        $fileFactory = new MockReaderFactory($fileReader);
        $zipFactory = new MockReaderFactory($zipReader);

        $resolver->addFactory($fileFactory, 'file');
        $resolver->addFactory($zipFactory, 'zip');

        $fileResource = ResourceUri::fromString('file://tests/archives/test/zip');
        $zipResource = ResourceUri::fromString('zip://file://tests/archives/test/zip');

        $this->assertSame($fileReader, $resolver->resolveReader($fileResource));
        $this->assertSame($zipReader, $resolver->resolveReader($zipResource));
    }
}

class MockReaderFactory implements ResourceReaderFactory
{
    private $reader;

    public function __construct(ResourceReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param ResourceUri $resource
     * @return ResourceReader
     */
    public function createReaderFor(ResourceUri $resource)
    {
        return $this->reader;
    }
}
