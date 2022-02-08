<?php

namespace ProcessMaker\Cli;

use \Install;
use Dotenv\Dotenv;

class Config
{
    public function packagesPath($package = null)
    {
        $path = $this->env('PACKAGES_PATH');
        if (!$path) {
            $path = Install::read()['packages_path'];
        }
        $path = $this->handleTrailingSlash($path, $package);
        return $path;
    }

    public function codebasePath()
    {
        $path = $this->env('CODEBASE_PATH');
        if (!$path) {
            $path = Install::read()['codebase_path'];
        }
        return $path;
    }

    public function cachePath()
    {
        return $this->env('CACHE_PATH');
    }
    
    public function cacheManifestPath()
    {
        return $this->cachePath() . '/' . 'manifest.json';
    }

    public function codebaseEnv()
    {
        return Dotenv::createArrayBacked($this->codebasePath())->load();
    }

    public function env($var, $suppressError = false)
    {
        if (!isset($_ENV[$var])) {
            if (!$suppressError) {
                throw new \Exception("$var environment variable not found");
            }
            return "";
        }
        return $_ENV[$var];
    }

    public function handleTrailingSlash($path, $package)
    {
        if ($package) {
            // if package is specified, add a trailing slash if it's missing
            $path = rtrim($path, '/') . '/';
            $path = $path . $package;
        } else {
            // otherwise, make sure there is no trailing slash
            $path = rtrim($path, '/');
        }
        return $path;
    }
}