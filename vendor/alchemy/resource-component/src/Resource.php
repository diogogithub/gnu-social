<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Resource;

use Alchemy\Resource\Metadata\MetadataIterator;

interface Resource
{

    /**
     * @return ResourceUri
     */
    public function getUri();

    /**
     * @return ResourceReader
     */
    public function getReader();

    /**
     * @return ResourceWriter
     */
    public function getWriter();

    /**
     * @return MetadataIterator
     */
    public function getMetadata();
}
