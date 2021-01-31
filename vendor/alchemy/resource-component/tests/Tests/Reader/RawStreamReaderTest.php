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

use Alchemy\Resource\Reader\RawStreamReader;

class RawStreamReaderTest extends \PHPUnit_Framework_TestCase
{

    public function getInvalidResources()
    {
        return [
            [ '' ],
            [ false ],
            [ 0 ],
            [ null ],
            [ new \stdClass() ],
            [ 'text' ],
            [ 12 ]
        ];
    }

    /**
     * @dataProvider getInvalidResources
     * @param mixed $resource
     */
    public function testCreateReaderWithInvalidResourcesTriggersAnError($resource)
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        (new RawStreamReader($resource));
    }

    public function testGetStreamReturnsUnderlyingStream()
    {
        $stream = fopen('php://temp', 'rw');
        $reader = new RawStreamReader($stream);

        $this->assertSame($stream, $reader->getContentsAsStream());
    }
}
