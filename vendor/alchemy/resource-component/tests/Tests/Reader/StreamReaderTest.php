<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Tests\Reader;

use Alchemy\Resource\Reader\StreamReader;
use Alchemy\Resource\ResourceUri;

class StreamReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testStreamsAreClosedWhenObjectIsDisposed()
    {
        $reader = new StreamReader(ResourceUri::fromString(__FILE__));
        $stream = $reader->getContentsAsStream();

        $this->assertTrue(is_resource($stream));

        unset($reader);
        gc_collect_cycles();

        $this->assertFalse(is_resource($stream));
    }

    public function testReaderTriggersErrorWhenResourceDoesNotExist()
    {
        $reader = new StreamReader(ResourceUri::fromString(__FILE__ . rand()));

        $this->setExpectedException(\RuntimeException::class);

        $reader->getContentsAsStream();
    }

    public function testReaderReturnsStreamPointingToCorrectResource()
    {
        $reader = new StreamReader(ResourceUri::fromString(__FILE__));
        $stream = $reader->getContentsAsStream();

        $actual = stream_get_contents($stream);
        $expected = file_get_contents(__FILE__);

        $this->assertEquals($expected, $actual);
    }
}
