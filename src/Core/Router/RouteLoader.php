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
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\Router;

use App\Core\Event;
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
     * @param mixed $resource
     */
    public function load($resource, ?string $type = null): RouteCollection
    {
        $this->rc = new RouteCollection();

        $route_files = glob(INSTALLDIR . '/src/Routes/*.php');
        $to_load     = [];
        foreach ($route_files as $file) {
            require_once $file;
            $ns = '\\App\\Routes\\' . basename($file, '.php');
            if (defined("{$ns}::LOAD_ORDER")) {
                $to_load[$ns::LOAD_ORDER] = $ns;
            } else {
                $to_load[] = $ns;
            }
        }

        ksort($to_load);
        foreach ($to_load as $ns) {
            $ns::load($this);
        }

        Event::handle('AddRoute', [&$this]);

        // Sort routes so that whichever route has the smallest accept option matches first, as it's more specific
        // This requires a copy, sadly, as it doesn't seem to be possible to modify the collection in-place
        // However, this is fine since this gets cached
        $it = $this->rc->getIterator();
        $it->uasort(fn (Route $left, Route $right) => count($left->getDefaults()['accept']) <=> count($right->getDefaults()['accept']));
        $this->rc = new RouteCollection();
        foreach ($it as $id => $route) {
            $this->rc->add($id, $route);
        }

        return $this->rc;
    }

    /**
     * Connect a route to a controller
     *
     * @param string     $id         Route unique id, used to generate urls, for instance
     * @param string     $uri_path   Path, possibly with {param}s
     * @param mixed      $target     Some kind of callable, typically class with `__invoke` or [object, method]
     * @param null|array $param_reqs Array of {param} => regex
     * @param null|array $options    Possible keys are ['condition', 'defaults', 'format',
     *                               'fragment', 'http-methods', 'locale', 'methods', 'schemes', 'accept', 'is_system_path']
     *                               'http-methods' and 'methods' are aliases
     */
    public function connect(string $id, string $uri_path, $target, ?array $options = [], ?array $param_reqs = [])
    {
        $this->rc->add($id,
            new Route(
                // path -- URI path
                path: $uri_path,
                // defaults = []     -- param default values,
                // and special configuration options
                defaults: array_merge(
                    [
                        '_controller'    => is_array($target) ? $target : [$target, '__invoke'],
                        '_format'        => $options['format'] ?? 'html',
                        '_fragment'      => $options['fragment'] ?? '',
                        '_locale'        => $options['locale'] ?? 'en',
                        'template'       => $options['template'] ?? '',
                        'accept'         => $options['accept'] ?? [],
                        'is_system_path' => $options['is_system_path'] ?? true,
                    ],
                    $options['defaults'] ?? []
                ),
                // requirements = [] -- param => regex
                requirements: $param_reqs,
                // options = []      -- possible keys: compiler_class:, utf8
                // No need for a special compiler class for now,
                // Enforce UTF8
                options: ['utf8' => true],
                // host = ''         -- hostname (subdomain, for instance) to match,
                // we don't want this
                host: '',
                // schemes = []      -- URI schemes (https, ftp and such)
                schemes: $options['schemes'] ?? [],
                // methods = []      -- HTTP methods
                methods: $options['http-methods'] ?? $options['methods'] ?? [],
                // condition = ''    -- Symfony condition expression,
                // see https://symfony.com/doc/current/routing.html#matching-expressions
                condition: isset($options['accept']) ? "request.headers.get('Accept') in " . json_encode($options['accept']) : ($options['condition'] ?? '')
            )
        );
    }

    /**
     * Whether this loader supports loading this route type
     * Passed the arguments from the `RoutingConfigurator::import` call from
     * `config/routes.php`
     *
     * @param mixed $resource
     * @codeCoverageIgnore
     */
    public function supports($resource, ?string $type = null): bool
    {
        return 'GNUsocial' === $type;
    }
}
