<?php

namespace App;

use App\DependencyInjection\Compiler\SchemaDefPass;
use const PHP_VERSION_ID;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        if (!\defined('INSTALLDIR')) {
            define('INSTALLDIR', dirname(__DIR__));
            define('SRCDIR', INSTALLDIR . '/src');
            define('PUBLICDIR', INSTALLDIR . '/public');
            define('GNUSOCIAL_ENGINE', 'GNU social');
            // MERGE Change to https://gnu.io/social/
            define('GNUSOCIAL_ENGINE_URL', 'https://gnusocial.network/');
            // MERGE Change to https://git.gnu.io/gnu/gnu-social
            define('GNUSOCIAL_ENGINE_REPO_URL', 'https://notabug.org/diogo/gnu-social/');
            // Current base version, major.minor.patch
            define('GNUSOCIAL_BASE_VERSION', '3.0.0');
            // 'dev', 'alpha[0-9]+', 'beta[0-9]+', 'rc[0-9]+', 'release'
            define('GNUSOCIAL_LIFECYCLE', 'dev');
            define('GNUSOCIAL_VERSION', GNUSOCIAL_BASE_VERSION . '-' . GNUSOCIAL_LIFECYCLE);
            define('GNUSOCIAL_CODENAME', 'Big bang');

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
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/*' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}/*' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}' . self::CONFIG_EXTS, '/', 'glob');
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SchemaDefPass($container));
    }
}
