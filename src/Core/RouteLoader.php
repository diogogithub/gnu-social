<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}


/**
 * Dynamic router loader and URLMapper interface atop Symfony's router
 *
 * Converts a path into a set of parameters, and vice versa
 *
 * @package   GNUsocial
 * @category  URL
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends Loader
{
    private RouteCollection $rc;

    /**
     * Route loading entry point, called from `config/routes.php`
     *
     * Must conform to symfony's interface, but the $resource is unused
     * and $type must not be null
     *
     * @param mixed       $resource
     * @param null|string $type
     *
     * @return RouteCollection
     */
    public function load($resource, string $type = null): RouteCollection
    {
        $this->rc = new RouteCollection();

        $route_files = glob(INSTALLDIR . '/src/Routes/*.php');
        foreach ($route_files as $file) {
            require_once $file;
            $ns = '\\App\\Routes\\' . basename($file, '.php');
            $ns::load($this);
        }

        return $this->rc;
    }

    /**
     * Connect a route to a controller
     *
     * @param string     $id
     * @param string     $uri_path
     * @param mixed      $target     Some kind of callable, typically [object, method]
     * @param null|array $param_reqs
     * @param null|array $options    Possible keys are ['condition', 'defaults', 'format',
     *                               'fragment', 'http-methods', 'locale', 'methods', 'schemes']
     *                               'http-methods' and 'methods' are aliases
     */
    public function connect(string $id, string $uri_path, $target, ?array $param_reqs = [], ?array $options = [])
    {
        $this->rc->add($id,
            new Route(
            // path -- URI path
                $uri_path,
                // defaults = []     -- param default values,
                // and special configuration options
                array_merge(
                    [
                        '_controller' => is_array($target) ? $target : [$target, '__invoke'],
                        '_format'     => $options['format'] ?? 'html',
                        '_fragment'   => $options['fragment'] ?? '',
                        '_locale'     => $options['locale'] ?? '',
                    ],
                    $options['defaults'] ?? []),
                // requirements = [] -- param => regex
                $param_reqs,
                // options = []      -- possible keys: compiler_class:, utf8
                // No need for a special compiler class for now,
                // Enforce UTF8
                ['utf8' => true],
                // host = ''         -- hostname (subdomain, for instance) to match,
                // we don't want this
                '',
                // schemes = []      -- URI schemes (https, ftp and such)
                $options['schemes'] ?? [],
                // methods = []      -- HTTP methods
                $options['http-methods'] ?? $options['methods'] ?? [],
                // condition = ''    -- Symfony condition expression,
                // see https://symfony.com/doc/current/routing.html#matching-expressions
                $options['condition'] ?? ''
            )
        );
    }

    /**
     * Whether this loader supports loading this route type
     * Passed the arguments from the `RoutingConfigurator::import` call from
     * `config/routes.php`
     *
     * @param mixed       $resource Unused
     * @param null|string $type
     *
     * @return bool
     */
    public function supports($resource, string $type = null)
    {
        return 'GNUsocial' === $type;
    }
}
