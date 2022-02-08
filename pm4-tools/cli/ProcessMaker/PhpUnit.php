<?php

namespace ProcessMaker\Cli;

use \CommandLine;
use \Config;
use \Packages;
use \FileSystem;
use DomDocument;
use DomXpath;

class PhpUnit 
{
    public function addTests($path)
    {
        $list = Packages::getEnterprisePackages();

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($path);

        $xpath = new DOMXpath($dom);
        $directories = $xpath->query('//testsuite[@name="Features"]')[0];

        foreach ($list as $package)
        {
            $testsDirectory = Config::packagesPath($package) . "/tests";
            if (FileSystem::exists($testsDirectory)) {
                $directory = $dom->createElement('directory', $testsDirectory);
                $directories->appendChild($directory);
            }
        }

        return $dom->save($path);
    }

    public function configFile()
    {
        return Config::codebasePath() . '/phpunit.xml';
    }

}