<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register a new ORM driver to allow use to use the old schemaDef format
 */
class SchemaDefCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->findDefinition('doctrine.orm.default_metadata_driver')
                  ->addMethodCall('addDriver',
                                  [new Reference('app.util.schemadef_driver'), 'App\\Entity']
                  );

        $container->setParameter('doctrine.orm.metadata.schemadef.class', App\Util\SchemaDefDriver::class);
    }
}
