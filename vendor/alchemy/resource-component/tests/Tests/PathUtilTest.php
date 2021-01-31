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

use Alchemy\Resource\PathUtil;

class PathUtilTest extends \PHPUnit_Framework_TestCase
{

    public function getPathsAndMatchingBaseNames()
    {
        return [
            [ '/absolute/path/to/file', 'file' ],
            [ '/absolute/path/to/file/with/extension.ext', 'extension.ext' ],
            [ 'relative/path/to/file', 'file' ],
            [ 'relative/path/to/file/with/extension.ext', 'extension.ext' ],
            [ 'pathless-file', 'pathless-file' ],
            [ 'pathless-file-with-extension.ext', 'pathless-file-with-extension.ext' ]
        ];
    }

    /**
     * @dataProvider getPathsAndMatchingBaseNames
     * @param string $path
     * @param string $expectedBaseName
     */
    public function testGetBasenameExtractsBasenameFromPath($path, $expectedBaseName)
    {
        $this->assertEquals($expectedBaseName, PathUtil::basename($path));
    }

    public function getPathsAndMatchingExtensions()
    {
        return [
            [ 'file.ext', 'ext' ],
            [ '/absolute/path/to/file/with/extension.ext', 'ext' ],
            [ 'relative/path/to/file', '' ],
            [ 'relative/path/to/file/with/extension.ext', 'ext' ],
            [ 'pathless-and-extensionless-file', '' ],
            [ 'pathless-file-with-extension.ext', 'ext' ],
            [ 'file-with-complex-extension.tar.gz', 'gz']
        ];
    }

    /**
     * @dataProvider getPathsAndMatchingExtensions
     *
     * @param string $path
     * @param string $expectedExtension
     */
    public function testGetExtensionExtractsExtension($path, $expectedExtension)
    {
        $this->assertEquals($expectedExtension, PathUtil::extractExtension($path));
    }
}
