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

use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\Writer\NullWriter;

class NullWriterTest extends \PHPUnit_Framework_TestCase
{

    public function testWriterDoesNotConsumeReader()
    {
        $reader = $this->prophesize(ResourceReader::class);
        $writer = new NullWriter();

        $reader->getContentsAsStream()->shouldNotBeCalled();

        $writer->writeFromReader($reader->reveal(), ResourceUri::fromString('test://target'));
    }
}
