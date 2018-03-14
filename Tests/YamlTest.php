<?php

declare(strict_types=1);

namespace Loevgaard\DandomainConsignmentBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class YamlTest extends TestCase
{
    public function testRouting()
    {
        $value = Yaml::parseFile(getcwd().'/Resources/config/routing.yml');
        $this->assertTrue(is_array($value));
    }

    public function testServices()
    {
        $value = Yaml::parseFile(getcwd().'/Resources/config/services.yml');
        $this->assertTrue(is_array($value));
    }

    public function testTranslations()
    {
        $finder = new Finder();
        $finder->files()->in(getcwd().'/Resources/translations');
        foreach($finder as $file) {
            $value = Yaml::parseFile($file->getPathname());
            $this->assertTrue(is_array($value));
        }
    }
}
