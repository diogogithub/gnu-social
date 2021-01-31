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

interface ResourceWriterResolver
{
    /**
     * Resolves a writer for the given resource URI.
     *
     * @param ResourceUri $resource
     * @return ResourceWriter
     */
    public function resolveWriter(ResourceUri $resource);
}
