<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource\Writer;

use Alchemy\Resource\ResourceReader;
use Alchemy\Resource\ResourceUri;
use Alchemy\Resource\ResourceWriter;

class StreamWriter implements ResourceWriter
{

    /**
     * @param ResourceReader $reader
     * @param ResourceUri $target
     */
    public function writeFromReader(ResourceReader $reader, ResourceUri $target)
    {
        $targetResource = @fopen($target->getUri(), 'w+', true);

        if (! is_resource($targetResource)) {
            throw new \RuntimeException('Unable to open ' . $target->getUri() . ' for writing');
        }

        $sourceResource = $reader->getContentsAsStream();
        $bytesCopied = @stream_copy_to_stream($sourceResource, $targetResource);

        if ($bytesCopied <= 0) {
            throw new \RuntimeException('Unable to write to ' . $target->getUri());
        };

        fclose($targetResource);
    }
}
