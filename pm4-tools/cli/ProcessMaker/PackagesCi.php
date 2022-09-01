<?php
namespace ProcessMaker\Cli;

use function ProcessMaker\Cli\info;
use \Git;
use \Config;
use \Composer;
use \CommandLine;
use \FileSystem;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemFilesystem;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystemFilesystem;
use Illuminate\Support\Arr;

class PackagesCi {

    private $branches;

    private $javascriptPackageBuildCommands = [
        "modeler" => 'npm run build-bundle',
        "screen-builder" => 'npm run build-bundle',
        "vue-form-elements" => 'npm run build-bundle',
    ];

    private $cacheManifest;
    private $remoteShas = [];

    public function build()
    {
        $this->buildCore();
        $this->buildPackages();
    }

    public function buildJavascript()
    {
        $javascriptPackages = Packages::getJavascriptPackages(Config::codebasePath());
        $this->installJavascriptPackages($javascriptPackages);
        $this->npmRunDev();
    }

    public function install()
    {
        $this->installCore();
        $list = Packages::getEnterprisePackages();

        // Our packages-to-install.php is ordered
        $list = $this->orderList($list);

        foreach ($list as $package) {
            info("Artisan installing $package");
            $this->artisanInstall($package);
        }

        $this->artisanCommand('optimize:clear');
        $this->artisanCommand('horizon:terminate');

        info("Modifying phpunit.xml to add package tests");
        PhpUnit::addTests(PhpUnit::configFile());
        // $this->exportDatabase();
        
        $this->cleanUp();

        info("Done");
    }

    public function cleanUp()
    {
        CommandLine::mustRun('rm -rf node_modules', Config::codebasePath());
    }

    public function shouldUseCache($package)
    {
        info("Checking cache for package $package");
        return $this->remoteSha($package) === $this->cachedSha($package);
    }

    public function fetchBranchSha($package, $branch = null)
    {
        if (!$branch) {
            $branch = 'HEAD';
        }

        $token = Config::env('GITHUB_TOKEN');
        $sha = CommandLine::mustRun("git ls-remote https://${token}@github.com/processmaker/$package $branch | awk '{ print $1}'");
        return trim($sha);
    }

    public function setCache($package)
    {
        if ($package === 'processmaker') {
            $from = Config::codebasePath();
        } else {
            $from = Config::packagesPath($package);
        }
        $to = Config::cachePath() . '/' . $package;
        $this->copy($from, $to);
        $this->updateManifest($package);
    }

    public function restoreCache($package)
    {
        if ($package === 'processmaker') {
            $to = Config::codebasePath();
        } else {
            $to = Config::packagesPath($package);
        }
        $from = Config::cachePath() . '/' . $package;
        $this->copy($from, $to);
    }

    public function copy($from, $to)
    {
        CommandLine::mustRun("mkdir -p $to");
        CommandLine::mustRun("ls -A1 | xargs rm -rf", $to);
        CommandLine::mustRun("rsync -r --exclude='/node_modules' --exclude='/vendor' ${from}/ ${to}");
    }

    private function cachedSha($package)
    {
        $manifest = $this->getCacheManifest();
        if (isset($manifest[$package])) {
            return $manifest[$package];
        }
        return null;
    }

    private function remoteSha($package)
    {
        if (isset($this->remoteShas[$package])) {
            $sha = $this->remoteShas[$package];
        } else {
            $sha = $this->fetchBranchSha($package, $this->getBranch($package));
            $this->remoteShas[$package] = $sha;
        }
        return $sha;
    }

    private function getCacheManifest()
    {
        if ($this->cacheManifest) {
            return $this->cacheManifest;
        }
        $manifest = [];
        if (FileSystem::exists(Config::cacheManifestPath())) {
            $manifest = json_decode(FileSystem::get(Config::cacheManifestPath()), true);
        }
        $this->cacheManifest = $manifest;
        return $manifest;
    }

    private function updateManifest($package)
    {
        $manifest = $this->getCacheManifest();
        $manifest[$package] = $this->remoteSha($package);
        $this->cacheManifest = $manifest;
        $manifest = json_encode($manifest);
        FileSystem::put(Config::cacheManifestPath(), $manifest);
    }

    private function composerRequireList($list)
    {
        return $list->map(function($package) {
            return "processmaker/$package";
        })->join(" ");
    }

    private function artisanInstall($package)
    {
        return $this->artisanCommand("${package}:install");
    }

    private function artisanCommand($cmd)
    {
        return CommandLine::runCommand("php artisan ${cmd}", function($code, $output) {
            throw new \Exception($output);
        }, Config::codebasePath());
    }

    public function getBranch($package)
    {
        if (!$this->branches) {
            $this->setBranches();
        }
        if (isset($this->branches[$package])) {
            return $this->branches[$package];
        }

        return null;
    }

    private function setBranches()
    {
        $result = preg_match_all('/ci:(.+?):(.+?)(\s|$)/', $this->pullRequestBody(), $matches);
        if ($result && $result > 0) {
            $this->branches = array_combine($matches[1], $matches[2]);
        } else {
            $this->branches = [];
        }
        $this->branches[$this->repoName()] = $this->pullRequestBranch();
    }

    public function getBranches()
    {
        return $this->branches;
    }

    private function pullRequestBody()
    {
        return Config::env('CI_PR_BODY');
    }

    private function pullRequestBranch()
    {
        return Config::env('CI_PACKAGE_BRANCH');
    }

    public function repoName()
    {
        return Config::env('CI_PROJECT');
    }

    private function installCommand()
    {
        extract($_ENV);
        if ($PM_APP_PORT !== "80") {
            $portWithPrefix = ":${PM_APP_PORT}";
        }

        return <<<END
        php artisan processmaker:install --no-interaction \
        --url=${PM_APP_URL}${portWithPrefix} \
        --broadcast-host=${PM_APP_URL}:${PM_BROADCASTER_PORT} \
        --username=admin \
        --password=admin123 \
        --email=admin@processmaker.com \
        --first-name=Admin \
        --last-name=User \
        --db-host=${DB_HOSTNAME} \
        --db-port=${DB_PORT} \
        --db-name=${DB_DATABASE} \
        --db-username=${DB_USERNAME} \
        --db-password=${DB_PASSWORD} \
        --data-driver=mysql \
        --data-host=${DB_HOSTNAME} \
        --data-port=${DB_PORT} \
        --data-name=${DB_DATABASE} \
        --data-username=${DB_USERNAME} \
        --data-password=${DB_PASSWORD} \
        --redis-host=${REDIS_HOST}
        END;
    }

    private function additionalEnv()
    {
        return <<<END

        PROCESSMAKER_SCRIPTS_DOCKER=/usr/local/bin/docker
        PROCESSMAKER_SCRIPTS_DOCKER_MODE=copying
        LARAVEL_ECHO_SERVER_AUTH_HOST=http://localhost
        SESSION_SECURE_COOKIE=false
        CACHE_DRIVER=redis
        SESSION_DOMAIN=null
        END;
    }

    private function buildCore()
    {
        $_ENV['COMPOSER_AUTH'] = '{"github-oauth": {"github.com": "' . $_ENV['GITHUB_TOKEN'] . '" }}';
        $this->cloneCore();
        CommandLine::mustRun('composer install --no-interaction', Config::codebasePath());
    }

    private function buildPackages()
    {
        info("Installing enterprise packages for CI");
        $list = Packages::getEnterprisePackages();

        info("Cloning " . $list->count() . " packages");
        foreach ($list as $package) {
            $branch = $this->getBranch($package);
            $info = "Cloning $package";
            if ($branch) {
                $info .= " with branch $branch";
            }
            info($info);
            Git::shallowClone($package, $branch, Config::packagesPath($package));
        }

        foreach ($list as $package) {
            info("Registering local repository path for $package");
            Composer::addRepositoryPath($package);
        }

        info("Composer requiring packages");
        $listString = $this->composerRequireList($list);
        Composer::require($listString);
    }

    private function installCore()
    {
        $this->createDatabase(Config::env('DB_DATABASE'));
        CommandLine::mustRun($this->installCommand(), Config::codebasePath());
        FileSystem::append(Config::codebasePath() . '/.env', $this->additionalEnv());
    }

    private function cloneCore()
    {
        $pm = 'processmaker';
        info("Cloning processmaker core");
        Git::shallowClone($pm, $this->getBranch($pm), Config::codebasePath());
    }

    public function databaseConnectionParams()
    {
        extract($_ENV);
        return "-h $DB_HOSTNAME -P $DB_PORT -u $DB_USERNAME -p'${DB_PASSWORD}'";
    }

    public function createDatabase($name)
    {
        $connection = $this->databaseConnectionParams();
        CommandLine::mustRun("mysql $connection -e 'DROP DATABASE IF EXISTS `$name`;'");
        CommandLine::mustRun("mysql $connection -e 'CREATE DATABASE `$name`;'");
    }

    public function exportDatabase()
    {
        $from = Config::env('DB_DATABASE');
        $to = 'test';
        $file = 'database.sql';
        $connection = $this->databaseConnectionParams();
        CommandLine::mustRun("mysqldump $connection $from > $file");
        $this->createDatabase($to);
        CommandLine::mustRun("mysql $connection $to < $file");
    }

    private function clonePackage($package)
    {
        Git::shallowClone($package, $this->getBranch($package), Config::packagesPath($package));
    }

    private function installJavascriptPackages($packages)
    {
        $packagesToBuild = [];
        foreach ($packages as $package) {

            // Only clone/build/link if specified in the PR
            // Otherwise, just use core's package.json
            if (!$this->getBranch($package)) {
                continue;
            }

            $path = Config::packagesPath($package);
            $this->clonePackage($package);

            // unsafe-perm is needed to run postinstall scripts as root
            CommandLine::mustRun("npm install --unsafe-perm", $path);
            $packagesToBuild[] = $package;
        }

        foreach ($packagesToBuild as $package) {
            $this->linkDependentPackages(Config::packagesPath($package));
            $this->buildJavascriptPackage($package);
        }

        return count($packagesToBuild);
    }

    private function linkDependentPackages($path)
    {
        $dependentPackages = Packages::getJavascriptPackages($path);
        foreach ($dependentPackages as $dependentPackage) {
            CommandLine::mustRun("mkdir -p node_modules/@processmaker", $path);
            $packagePath = Config::packagesPath($dependentPackage);
            $nodeModulesPackagePath = "node_modules/@processmaker/${dependentPackage}";
            CommandLine::mustRun("rm -rf $nodeModulesPackagePath", $path);
            CommandLine::mustRun("ln -s $packagePath $nodeModulesPackagePath", $path);
        }
    }

    private function buildJavascriptPackage($package)
    {
        if (isset($this->javascriptPackageBuildCommands[$package])) {
            $cmd = $this->javascriptPackageBuildCommands[$package];
            CommandLine::mustRun($cmd, Config::packagesPath($package));
        }
        CommandLine::mustRun('rm -rf node_modules', Config::packagesPath($package));
    }

    private function npmRunDev()
    {
        // unsafe-perm is needed to run postinstall scripts as root
        info("Starting at " . date('c'));
        CommandLine::mustRun('npm ci --unsafe-perm --omit=dev', Config::codebasePath());
        $this->linkDependentPackages(Config::codebasePath());
        CommandLine::mustRun('npm install cross-env', Config::codebasePath());
        CommandLine::mustRun('npm run dev', Config::codebasePath());
        info("Finished at " . date('c'));
    }

    private function orderList($list)
    {
        return $list->sortBy(function($package) {
            switch($package) {
                // Must be installed first
                case 'package-data-vocabularies':
                    return 0;

                // Must be installed last
                case 'connector-docusign':
                    return 2;

                default:
                    return 1;
            }
        });
    }
}
