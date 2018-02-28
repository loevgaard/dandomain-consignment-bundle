<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Tests\DependencyInjection;

use Loevgaard\DandomainConsignmentBundle\DependencyInjection\LoevgaardDandomainConsignmentExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

class LoevgaardDandomainConsignmentExtensionTest extends TestCase
{
    public function testThrowsExceptionUnlessAltapayUsernameSet()
    {
        $this->expectException(InvalidConfigurationException::class);

        $loader = new LoevgaardDandomainConsignmentExtension();
        $config = $this->getEmptyConfig();
        unset($config['report_dir']);
        $loader->load([$config], new ContainerBuilder());
    }

    public function testGettersSetters()
    {
        $loader = new LoevgaardDandomainConsignmentExtension();
        $config = $this->getEmptyConfig();
        $container = new ContainerBuilder();
        $loader->load([$config], $container);

        $this->assertSame($config['report_dir'], $container->getParameter('loevgaard_dandomain_consignment.report_dir'));
    }

    /**
     * @return array
     */
    protected function getEmptyConfig()
    {
        $yaml = <<<EOF
report_dir: report_dir
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }
}
