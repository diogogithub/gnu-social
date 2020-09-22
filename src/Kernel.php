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
 * Symfony Kernel, which is responsible for configuring the whole application
 *
 * @package GNUsocial
 * @category Kernel
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App;

use App\DependencyInjection\Compiler\ModuleManagerPass;
use App\DependencyInjection\Compiler\SchemaDefPass;
use const PHP_VERSION_ID;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        if (!defined('INSTALLDIR')) {
            define('INSTALLDIR', dirname(__DIR__));
            define('SRCDIR', INSTALLDIR . '/src');
            define('PUBLICDIR', INSTALLDIR . '/public');
            define('GNUSOCIAL_ENGINE_NAME', 'GNU social');
            // MERGE Change to https://gnu.io/social/
            define('GNUSOCIAL_PROJECT_URL', 'https://gnusocial.network/');
            // MERGE Change to https://git.gnu.io/gnu/gnu-social
            define('GNUSOCIAL_REPOSITORY_URL', 'https://notabug.org/diogo/gnu-social/');
            // Current base version, major.minor.patch
            define('GNUSOCIAL_BASE_VERSION', '3.0.0');
            // 'dev', 'alpha[0-9]+', 'beta[0-9]+', 'rc[0-9]+', 'release'
            define('GNUSOCIAL_LIFECYCLE', 'dev');
            define('GNUSOCIAL_VERSION', GNUSOCIAL_BASE_VERSION . '-' . GNUSOCIAL_LIFECYCLE);
            define('GNUSOCIAL_CODENAME', 'Big bang');
            define('URL_REGEX_DOMAIN_NAME', '(?:(?!-)[A-Za-z0-9\-]{1,63}(?<!-)\.)+[A-Za-z]{2,10}');

            // Work internally in UTC
            date_default_timezone_set('UTC');

            // Work internally with UTF-8
            mb_internal_encoding('UTF-8');
        }
    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);

        $confDir = $this->getProjectDir() . '/config';

        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');

        // Overriding doesn't work as we want, overrides the top-most key, do it manually
        $loader->load(INSTALLDIR . '/social.local' . self::CONFIG_EXTS, 'glob');
        $locals = $container->getParameter('gnusocial');
        $loader->load(INSTALLDIR . '/social' . self::CONFIG_EXTS, 'glob');
        $defaults = $container->getParameter('gnusocial');
        $configs  = array_replace_recursive($defaults, $locals);
        $container->setParameter('gnusocial', $configs);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $config = \dirname(__DIR__) . '/config';
        $routes->import($config . '/{routes}/' . $this->environment . '/*.yaml');
        $routes->import($config . '/{routes}/*.yaml');

        if (is_file($config . '/routes.yaml')) {
            $routes->import($config . '/{routes}.yaml');
        } elseif (is_file($path = $config . '/routes.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ModuleManagerPass());
        $container->addCompilerPass(new SchemaDefPass());
    }
}
