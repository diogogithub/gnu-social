<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Tests\Writer;

use Alchemy\Resource\Reader\StreamReader;
use Alchemy\Resource\Reader\StringReader;
use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\Writer\FilesystemWriter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FilesystemWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setup()
    {
        $this->root = vfsStream::setup('tests');
    }

    public function testWriterWritesToStreamResource()
    {
        $this->root->addChild(vfsStream::newFile('dummy-resource.txt'));
        $uri = $this->root->getChild('dummy-resource.txt')->url();

        $resource = ResourceUri::fromString($uri);

        $reader = new StringReader('mock data');
        $writer = new FilesystemWriter();

        $writer->writeFromReader($reader, $resource);

        $streamReader = new StreamReader($resource);

        $this->assertEquals('mock data', stream_get_contents($streamReader->getContentsAsStream()));

        unset($streamReader);
    }

}
