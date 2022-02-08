#!/usr/bin/env php
<?php

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} elseif (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require Config::env('HOME').'/.composer/vendor/autoload.php';
}

use Silly\Application;
use Illuminate\Container\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use function ProcessMaker\Cli\info;
use function ProcessMaker\Cli\table;
use function ProcessMaker\Cli\output;
use function ProcessMaker\Cli\warning;
use function ProcessMaker\Cli\resolve;
use function ProcessMaker\Cli\warningThenExit;

$dotEnvDir = null;
if (file_exists(getcwd() . '/.env')) {
    $dotEnvDir = getcwd();

} elseif (file_exists(__DIR__ . '/../.env')) {
    $dotEnvDir = __DIR__ . '/../';
}
if ($dotEnvDir) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotEnvDir);
    $dotenv->load();
}

Container::setInstance(new Container);

$app = new Application('ProcessMaker CLI Tool', '0.5.0');

if (!FileSystem::isDir(PM_HOME_PATH)) {
    /*
	 * -------------------------------------------------+
	 * |                                                |
	 * |    Command: Install Cli                        |
	 * |                                                |
	 * -------------------------------------------------+
	 */
    $app->command('install-cli', function (InputInterface $input, OutputInterface $output) {

        // First thing we want to do is ask the user to
        // tell us the absolute path to the core codebase
        $helper = $this->getHelperSet()->get('question');

		// Callback to autocomplete the directories available
	    // as the user is typing
        $callback = function (string $userInput): array {

            $inputPath = preg_replace('%(/|^)[^/]*$%', '$1', $userInput);
            $inputPath = '' === $inputPath ? '.' : $inputPath;
            $foundFilesAndDirs = @scandir($inputPath) ?: [];

            return array_map(static function ($dirOrFile) use ($inputPath) {
                return $inputPath.$dirOrFile;
            }, $foundFilesAndDirs);
        };

        $question = "<info>Please enter the absolute path to the local copy of the processmaker/processmaker codebase</info>:".PHP_EOL;
        $question = new Question($question);
		$question->setAutocompleterCallback($callback);
        $codebase_path = $helper->ask($input, $output, $question);

        // Make sure they entered something
        if (null === $codebase_path) {
            warningThenExit('You must enter a valid absolute path to continue the installation. Please try again.');
        }

        // Check for composer.json
        if (!FileSystem::exists("$codebase_path/composer.json")) {
            warningThenExit("Could not the composer.json for processmaker/processmaker in: $codebase_path");
        }

        // Next we need to know where all of the local copies
        // of the processmaker/* packages will be stored
        $question = "<info>Please enter the absolute path to the directory where local copies of the ProcessMaker packages will be stored:</info>:".PHP_EOL;
        $question = new Question($question);
        $question->setAutocompleterCallback($callback);
		$packages_path = $helper->ask($input, $output, $question);

        // Make sure they entered something
        if (null === $packages_path) {
            warningThenExit('You must enter a valid absolute path to continue the installation. Please try again.');
        }

		// Creates the sudoers entry and the base config file/directory
        Install::install($codebase_path, $packages_path);

		info('Installation complete!');

    })->descriptions('Runs the installation process for this tool. Necessary before other commands will appear.');

} else {

    /*
	 * -------------------------------------------------+
	 * |                                                |
	 * |    Command: Install Packages                   |
	 * |                                                |
	 * -------------------------------------------------+
	 */
    $app->command('install-packages [-4|--for_41_develop]', function (InputInterface $input, OutputInterface $output) {

        // Indicates if we should install the 4.1-develop
        // versions of each package or the 4.2
        $for_41_develop = $input->getOption('for_41_develop');

        // Should the output be verbose or not
        $verbose = $input->getOption('verbose');

        // Use an anonymous function to we can easily re-run if
        // we decide to force the installation of the packages
        $build_install_commands = static function ($force = false) use ($for_41_develop) {
            return Packages::buildPackageInstallCommands($for_41_develop, $force);
        };

        // Builds an array of commands to run in the local
        // processmaker/processmaker codebase to require
        // each supported package, then install it and
        // publish it's vendor assets (if any are available)
        try {
            $install_commands = $build_install_commands();
        } catch (DomainException $exception) {

            // Show the user the incompatible branch information
            warning($exception->getMessage());

            // Ask the user if they want to force the install anyway
            $helper = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion('<comment>Force install the packages?</comment> "No" to abort or "Yes" to proceed: ', false);

            // Bail out if they user doesn't want to force the install
            if (false === $helper->ask($input, $output, $question)) {
                warningThenExit('Packages install aborted.');
            }

            // Re-run the install but add the force argument to
            // prevent the incompatible exception from being thrown
            $install_commands = $build_install_commands(true);
        }

        // Grab an instance of the CommandLine class
        $cli = resolve(\ProcessMaker\Cli\CommandLine::class);

        // Create a progress bar and start it
        $cli->createProgressBar($install_commands->flatten()->count(), 'message');

        // Set the initial message and start up the progress bar
        $cli->getProgress()->setMessage('Starting install...');
        $cli->getProgress()->start();

        // Iterate through the collection of commands
        foreach ($install_commands as $package => $command_collection) {

            // Iterate through each command and attempt to run it
            foreach ($command_collection as $command) {

                // Update the progress bar
                $cli->getProgress()->setMessage("Installing $package...");
                $cli->getProgress()->advance();

                try {
                    $command_output = $cli->runAsUser($command, static function ($exitCode, $out) {
                        throw new RuntimeException($out);
                    }, Config::codebasePath());
                } catch (RuntimeException $exception) {

                    $cli->getProgress()->clear();

                    output("<fg=red>Command Failed:</> $command");
                    output($exception->getMessage());

                    $cli->getProgress()->display();

                    continue;
                }

                if (!$verbose) {
                    continue;
                }

                // If the user wants verbose output, then show the
                // stdout for all of the successful commands as well
                // (in addition to the ones which failed)
                $cli->getProgress()->clear();

                output("<info>Command Success:</info> $command");
                output($command_output);

                $cli->getProgress()->display();
            }
        }

        // Clean up the progress bar
        $cli->getProgress()->finish();
        $cli->getProgress()->clear();

        // See how long it took to run everything
        $timing = $cli->timing();

        // Output and we're done!
        output(PHP_EOL."<info>Finished in</info> $timing");

    })->descriptions('Installs all enterprise packages in the local ProcessMaker core (processmaker/processmaker) codebase.', [
        '--for_41_develop' => 'Uses 4.1 version of the supported packages'
    ]);

    /*
     * -------------------------------------------------+
     * |                                                |
     * |    Command: Packages                           |
     * |                                                |
     * -------------------------------------------------+
     */
    $app->command('packages', function () {

        table(['Name', 'Version', 'Branch', 'Commit Hash'], Packages::getPackagesTableData());

    })->descriptions('Display the current version, branch, and names of known local packages');

    /*
     * -------------------------------------------------+
     * |                                                |
     * |    Command: Pull                               |
     * |                                                |
     * -------------------------------------------------+
     */
    $app->command('pull [-4|--for_41_develop]', function (InputInterface $input, OutputInterface $output) {

        // Updates to 4.1-branch of packages (or not)
        $for_41_develop = $input->getOption('for_41_develop');

        // Set verbosity level of output
        $verbose = $input->getOption('verbose');

        // Put everything together and run it
        $for_41_develop ? Packages::pull41($verbose) : Packages::pull($verbose);

    })->descriptions('Cycles through each local store of supported ProcessMaker 4 packages.',
        ['--for_41_develop' => 'Change each package to the correct version for the 4.1 version of processmaker/processmaker']
    );

    /*
     * -------------------------------------------------+
     * |                                                |
     * |    Command: Clone All                          |
     * |                                                |
     * -------------------------------------------------+
     */
    $app->command('clone-all [-f|--force]', function ($force = null) {
        foreach (Packages::getSupportedPackages() as $index => $package) {
            try {
                if (Packages::clonePackage($package)) {
                    info("Package $package cloned successfully!");
                }
            } catch (Exception $exception) {
                warning($exception->getMessage());
            }
        }
    })->descriptions('Clone all supported ProcessMaker 4 packages to a local directory', [
        '--force' => 'Delete the package locally if it exists already'
    ]);

}

/*
* -------------------------------------------------+
* |                                                |
* |    Command: Install Packages CI                |
* |                                                |
* -------------------------------------------------+
*/
$app->command('build-ci', function() {
    PackagesCi::build();
});

$app->command('build-javascript-ci', function() {
    PackagesCi::buildJavascript();
});

$app->command('install-ci', function() {
    PackagesCi::install($this);
});

$app->command('test-runner', function() {
});

$app->run();
