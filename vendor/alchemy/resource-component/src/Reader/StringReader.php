<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Reader;

use Alchemy\Resource\ResourceReader;

class StringReader implements ResourceReader
{
    /**
     * @var string
     */
    private $contents;

    /**
     * @param string $contents
     */
    public function __construct($contents)
    {
        $this->contents = (string) $contents;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @return resource
     */
    public function getContentsAsStream()
    {
        $stream = fopen('php://temp', 'rw');

        fwrite($stream, $this->contents);
        fseek($stream, 0);

        return $stream;
    }
}
