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

use Alchemy\Resource\ResourceUri;

class ResourceUriTest extends \PHPUnit_Framework_TestCase
{

    public function getUrisAndExpectedValidationResult()
    {
        return [
            [ 'file://', false ],
            [ '://', false ],
            [ ':///', false ],
            [ '/', false ],
            [ '/protocol/less/path', false ],
            [ 'protocol/less/relative/path', false ],
            [ 'file:///', true ],
            [ 'file:///absolute/path/', true ],
            [ 'file://relative/path/', true ],
            [ 'any://test', true ],
            [ 'daisy://file://path', true ],
            [ 'nested://daisy://file://path', true ],
            [ 'invalid://daisy://://path', false ],
            [ 'invalid://://daisy://path', false ]
        ];
    }

    /**
     * @dataProvider getUrisAndExpectedValidationResult
     * @param string $uri A valid URI
     * @param bool $expected The expected validation result
     */
    public function testUrisAreCorrectlyValidated($uri, $expected)
    {
        $this->assertEquals(
            $expected,
            ResourceUri::isValidUri($uri),
            'ResourceUri::isValidUri(' . $uri . ') should return ' . $expected ? 'true' : 'false'
        );
    }

    public function getChainedUris()
    {
        return [
            [ 'daisy://file://path', 'file://path' ],
            [ 'nested://daisy://file://path', 'daisy://file://path' ]
        ];
    }

    /**
     * @dataProvider getChainedUris
     * @param string $uri A chained resource URI
     * @param string $chainedResourceUri The chained resource's URI
     */
    public function testChainedResourcesAreCorrectlyDetected($uri, $chainedResourceUri)
    {
        $uri = new ResourceUri($uri);

        $this->assertTrue(
            $uri->hasChainedResource(),
            'ResourceUri[' . $uri . ']::hasChainedResource() should return true '
        );

        $this->assertEquals(
            $chainedResourceUri,
            $uri->getChainedResource()->getUri(),
            'ResourceUri[' . $uri . ']::getChainedResource() should return ResourceUri[' . $chainedResourceUri . '].'
        );
    }

    public function getMalformedUris()
    {
        return [
            [ 'file://' ],
            [ '://' ],
            [ ':///' ],
            [ 'invalid://daisy://://path' ],
            [ 'invalid://://daisy://path' ]
        ];
    }

    /**
     * @dataProvider getMalformedUris
     * @param string $uri A malformed URI chain
     */
    public function testMalformedUrisTriggerAnError($uri)
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        ResourceUri::fromString($uri);
    }

    public function getUrisWithoutProtocol()
    {
        return [
            [ '/', 'file:///' ],
            [ '/absolute/path', 'file:///absolute/path' ],
            [ 'relative/path', 'file://relative/path' ]
        ];
    }

    /**
     * @dataProvider getUrisWithoutProtocol
     * @param string $uri
     * @param string $expectedUri
     */
    public function testFromStringAppendsDefaultProtocol($uri, $expectedUri)
    {
        $resourceUri = ResourceUri::fromString($uri);

        $this->assertEquals(
            $expectedUri,
            $resourceUri->getUri(),
            'Parsing "' . $uri . '" should evaluate as "' . $expectedUri . '"'
        );
    }

    public function getUrisWithKnownProtocolAndResource()
    {
        return [
            [ 'file://resource', 'file', 'resource' ],
            [ 'http://resource', 'http', 'resource' ]
        ];
    }

    /**
     * @dataProvider getUrisWithKnownProtocolAndResource
     * @param string $uri
     * @param string $expectedProtocol
     * @param string $expectedResource
     */
    public function testProtocolAndResourceAreCorrectlyParsed($uri, $expectedProtocol, $expectedResource)
    {
        $resourceUri = new ResourceUri($uri);

        $this->assertEquals($expectedProtocol, $resourceUri->getProtocol());
        $this->assertEquals($expectedResource, $resourceUri->getResource());
    }

    public function testToStringReturnsSourceUri()
    {
        $resourceUri = new ResourceUri('file://path');

        $this->assertEquals('file://path', (string) $resourceUri);
    }

    public function getUrisForEqualityComparaison()
    {
        return [
            [ 'test://equals', 'test://equals', true ],
            [ 'test://equals', 'test://not-equals', false ]
        ];
    }

    /**
     * @dataProvider getUrisForEqualityComparaison
     * @param string $lhsUri
     * @param string $rhsUri
     * @param bool $expectedResult
     */
    public function testEqualsMatchesEquivalentUris($lhsUri, $rhsUri, $expectedResult)
    {
        $lhs = ResourceUri::fromString($lhsUri);
        $rhs = ResourceUri::fromString($rhsUri);

        $this->assertEquals((bool) $expectedResult, $lhs->equals($rhs));
        $this->assertEquals((bool) $expectedResult, $rhs->equals($lhs));
    }

    public function testCreatingFromProtocolAndResourceCreatesCorrectResource()
    {
        $protocol = 'test';
        $resource = 'test-resource';

        $uri = ResourceUri::fromProtocolAndResource($protocol, $resource);

        $this->assertEquals('test://test-resource', (string) $uri);
    }

    public function testCreatingFromStringArrayReturnsArrayOfResources()
    {
        $uris = [
            'test://first-resource',
            'test://second-resource'
        ];

        $resources = array_values(ResourceUri::fromStringArray($uris));

        $this->assertEquals($uris[0], (string) $resources[0]);
        $this->assertEquals($uris[1], (string) $resources[1]);
    }

    public function testCreatingFromStringArrayWithInvalidValuesTriggersError()
    {
        $uris = [
            'test::/first-resource',
            '://second-resource'
        ];

        $this->setExpectedException(\InvalidArgumentException::class);

        array_values(ResourceUri::fromStringArray($uris));
    }

    public function testChainingWrapsUriInNewProtocol()
    {
        $uri = ResourceUri::fromString('nested://uri')->chain('wrapper');

        $this->assertEquals('wrapper://nested://uri', (string) $uri);
        $this->assertEquals('wrapper', $uri->getProtocol());
        $this->assertEquals('nested://uri', $uri->getResource());
        $this->assertTrue($uri->hasChainedResource(), 'Chained URI should report having a chained resource');
        $this->assertEquals('nested://uri', (string) $uri->getChainedResource());
    }

    public function testCreatingChildAppendsRelativePathToUri()
    {
        $this->assertEquals('root://uri/child', (string) ResourceUri::fromString('root://uri')->child('child'));
        $this->assertEquals('root://uri/child', (string) ResourceUri::fromString('root://uri')->child('/child'));
    }

    public function testGetPathReturnsEmptyStringForPathlessResources()
    {
        $uri = ResourceUri::fromString('mock://path-less');

        $this->assertEquals('', $uri->getPath());
    }

    public function testGetPathReturnsRelativePath()
    {
        $uri = ResourceUri::fromString('mock://resource')->child('path');

        $this->assertEquals('path', $uri->getPath());
    }

    public function testGetPathFromChainedResourceReturnsRelativePath()
    {
        $uri = ResourceUri::fromString('mock://resource')->child('path')->chain('chained');

        $this->assertEquals('path', $uri->getPath());
    }
}
