<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Resource\Metadata;

use Alchemy\Resource\Metadata\LazyMetadataIterator;
use Alchemy\Resource\Metadata\MetadataIterator;
use Alchemy\Resource\Metadata\NullMetadataIterator;

class LazyMetadataIteratorTest extends \PHPUnit_Framework_TestCase
{

    public function testUnderlyingIteratorIsNotInitializedWhenCreatingLazyIterator()
    {
        $called = false;
        $factory = function () use (& $called) {
            $called = true;

            return new NullMetadataIterator();
        };

        (new LazyMetadataIterator($factory));

        $this->assertFalse($called, 'Factory method should not have been called by lazy iterator');
    }

    public function testCallableFactoryIsOnlyInvokedOnce()
    {
        $callCount = 0;
        $factory = function () use (& $callCount) {
            $callCount++;

            return new NullMetadataIterator();
        };

        $iterator = new LazyMetadataIterator($factory);

        iterator_to_array($iterator);
        iterator_to_array($iterator);

        $this->assertEquals(1, $callCount, 'Factory method should only be called once.');
    }

    public function testIteratorMethodCallsShouldBeDelegatedToUnderlyingIterator()
    {
        $underlyingIterator = $this->prophesize(MetadataIterator::class);
        $factory = function () use ($underlyingIterator) {
            return $underlyingIterator->reveal();
        };

        $underlyingIterator->next()->shouldBeCalled();
        $underlyingIterator->key()->shouldBeCalled();
        $underlyingIterator->current()->shouldBeCalled();
        $underlyingIterator->rewind()->shouldBeCalled();
        $underlyingIterator->valid()->shouldBeCalled();

        $iterator = new LazyMetadataIterator($factory);

        $iterator->next();
        $iterator->key();
        $iterator->current();
        $iterator->rewind();
        $iterator->valid();
    }
}
