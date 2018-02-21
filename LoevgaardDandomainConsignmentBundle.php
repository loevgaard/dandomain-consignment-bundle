<?php

namespace Loevgaard\DandomainConsignmentBundle;

use Loevgaard\DandomainConsignmentBundle\DependencyInjection\Compiler\ConsignmentServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LoevgaardDandomainConsignmentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConsignmentServicePass());
    }
}
