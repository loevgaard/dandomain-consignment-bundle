<?php

namespace Loevgaard\DandomainConsignmentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConsignmentServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('loevgaard_dandomain_consignment.consignment_service_collection')) {
            return;
        }

        $definition = $container->findDefinition('loevgaard_dandomain_consignment.consignment_service_collection');

        $taggedServices = $container->findTaggedServiceIds('ldc.consignment_service');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addConsignmentService', [new Reference($id)]);
        }
    }
}
