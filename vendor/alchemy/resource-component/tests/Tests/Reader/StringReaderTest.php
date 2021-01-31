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

use Alchemy\Resource\Reader\StringReader;

class StringReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testGetContentsAsStreamContainingSavedString()
    {
        $expected = 'hello world !';
        $reader = new StringReader($expected);

        $stream = $reader->getContentsAsStream();
        $streamContents = stream_get_contents($stream);

        fclose($stream);

        $this->assertEquals($expected, $streamContents);
    }

    public function testGetContentsReturnsSavedString()
    {
        $expected = 'goodbye world !';
        $reader = new StringReader($expected);

        $this->assertEquals($expected, $reader->getContents());
    }
}
