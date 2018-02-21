<?php
namespace Loevgaard\DandomainConsignmentBundle\DependencyInjection\Compiler;

use Loevgaard\DandomainConsignmentBundle\ConsignmentService\ConsignmentServiceCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ConsignmentServicePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ConsignmentServiceCollection::class)) {
            return;
        }

        $definition = $container->findDefinition(ConsignmentServiceCollection::class);

        $taggedServices = $container->findTaggedServiceIds('ldc.consignment_service');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addConsignmentService', [new Reference($id)]);
        }
    }
}