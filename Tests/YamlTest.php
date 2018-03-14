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

    public function testTranslationsValidYaml()
    {
        $finder = new Finder();
        $finder->files()->in(getcwd().'/Resources/translations');
        foreach ($finder as $file) {
            $value = Yaml::parseFile($file->getPathname());
            $this->assertTrue(is_array($value));
        }
    }

    public function testTranslations()
    {
        $control = $this->flattenYamlFile(getcwd().'/Resources/translations/LoevgaardDandomainConsignmentBundle.en.yml');

        $finder = new Finder();
        $finder->files()->in(getcwd().'/Resources/translations');
        foreach ($finder as $file) {
            $test = $this->flattenYamlFile($file->getPathname());

            $this->assertSame($control, $test);
        }
    }

    private function flattenYamlFile(string $file): array
    {
        $arr = Yaml::parseFile($file);
        $str = str_replace(['[', ']'], ['_', ''], urldecode(http_build_query($arr)));
        parse_str($str, $flat);

        $keys = array_keys($flat);
        sort($keys);

        return $keys;
    }
}
