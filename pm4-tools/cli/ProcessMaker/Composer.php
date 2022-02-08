<?php

namespace ProcessMaker\Cli;

use LogicException, RuntimeException;
use DomainException;
use \Git as GitFacade;
use \Packages as PackagesFacade;
use \FileSystem as FileSystemFacade;
use \CommandLine as CommandLineFacade;
use \Config as ConfigFacade;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class Composer
{
    /**
     * @param  string  $path_to_composer_json
     *
     * @return mixed
     */
    public function getComposerJson(string $path_to_composer_json)
    {
        if (!FileSystemFacade::isDir($path_to_composer_json)) {
            throw new LogicException("Path to composer.json not found: $path_to_composer_json");
        }

        if (Str::endsWith($path_to_composer_json, '/')) {
            $path_to_composer_json = Str::replaceLast('/', '', $path_to_composer_json);
        }

        $composer_json_file = "$path_to_composer_json/composer.json";

        if (!FileSystemFacade::exists($composer_json_file)) {
            throw new RuntimeException("Composer.json not found: $composer_json_file");
        }

        return json_decode(FileSystemFacade::get($composer_json_file), false);
    }

    public function addRepositoryPath($package)
    {
        $packagesPath = ConfigFacade::packagesPath($package);
        CommandLineFacade::runCommand("composer config repositories.pm4-{$package} path ${packagesPath}", function($code, $output) {
            throw new \Exception($output);
        }, ConfigFacade::codebasePath());
    }

    public function require($packages)
    {
        CommandLineFacade::runCommand("composer require --no-interaction $packages", function($code, $output) {
            throw new \Exception($output);
        }, ConfigFacade::codebasePath());
    }
}
