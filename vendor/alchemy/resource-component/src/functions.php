<?php

/*
 * This file is part of alchemy/resource-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @param string|\Alchemy\Resource\ResourceUri $uri A valid resource URI instance or string.
 * @return \Alchemy\Resource\ResourceUri
 */
function uri($uri) {
    if ($uri instanceof \Alchemy\Resource\ResourceUri) {
        return $uri;
    }

    return \Alchemy\Resource\ResourceUri::fromString($uri);
}
