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

use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\ResourceWriter;
use Alchemy\Resource\Resolver\ProtocolBasedWriterResolver;

class ProtocolBasedWriterResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testResolverTriggersAnErrorForUnsupportedProtocols()
    {
        $this->setExpectedException(\RuntimeException::class);

        $resolver = new ProtocolBasedWriterResolver();
        $resource = new ResourceUri('file://tests/archives/test.zip');

        $resolver->resolveWriter($resource);
    }

    public function testResolverResolvesWriterForRegisteredProtocols()
    {
        $resolver = new ProtocolBasedWriterResolver();

        $fileWriter = $this->prophesize(ResourceWriter::class)->reveal();
        $zipWriter = $this->prophesize(ResourceWriter::class)->reveal();

        $resolver->addWriter($fileWriter, 'file');
        $resolver->addWriter($zipWriter, 'zip');

        $fileResource = ResourceUri::fromString('file://tests/archives/test/zip');
        $zipResource = ResourceUri::fromString('zip://file://tests/archives/test/zip');

        $this->assertSame($fileWriter, $resolver->resolveWriter($fileResource));
        $this->assertSame($zipWriter, $resolver->resolveWriter($zipResource));
    }
}
