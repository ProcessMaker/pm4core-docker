<?php

namespace ProcessMaker\Cli;

use Exception;
use Illuminate\Container\Container;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

// if (!defined('BREW_PREFIX')) {
//     define('BREW_PREFIX', (new CommandLine())->runAsUser('printf $(brew --prefix)'));
// }

if (!defined('PM_HOME_PATH')) {
    define('PM_HOME_PATH', $_SERVER['HOME'] . '/.config/pm');
}

if (!defined('USER_HOME')) {
    define('USER_HOME', getenv('HOME'));
}

/**
 * @param  string  $output
 * @param  int  $exitCode
 *
 * @return string
 */
function warningThenExit(string $output, int $exitCode = 0): string {
    return warning($output) . exit($exitCode);
}

/**
 * Resolve the given class from the container.
 *
 * @param  string  $class
 *
 * @return mixed
 * @throws \Illuminate\Contracts\Container\BindingResolutionException
 */
function resolve(string $class) {
    return Container::getInstance()->make($class);
}

/**
 * Swap the given class implementation in the container.
 *
 * @param  string  $class
 * @param  mixed  $instance
 *
 * @return void
 */
function swap(string $class, $instance) {
    Container::getInstance()->instance($class, $instance);
}

/**
 * @return mixed
 */
function user() {
    if (! isset($_SERVER['SUDO_USER'])) {
        return $_SERVER['USER'];
    }

    return $_SERVER['SUDO_USER'];
}

/**
 * Verify that the script is currently running as "sudo".
 *
 * @return void
 * @throws \Exception
 */
function should_be_sudo() {
    if (!isset($_SERVER['SUDO_USER'])) {
        throw new Exception('This command must be run with sudo.');
    }
}

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 *
 * @return void
 */
function info(string $output) {
    $secrets = [
        Config::env('GITHUB_TOKEN', true)
    ];
    $output = str_replace($secrets, '****', $output);
    output('<info>'.$output.'</info>');
}

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 *
 * @return void
 */
function warning(string $output) {
    output('<fg=red>' . $output . '</>');
}

/**
 * Output a table to the console.
 *
 * @param array $headers
 * @param array $rows
 * @return void
 */
function table(array $headers = [], array $rows = [])  {
    $table = new Table(new ConsoleOutput);
    $table->setHeaders($headers)->setRows($rows);
    $table->render();
}

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 *
 * @return void
 */
function output(string $output) {
    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
        return;
    }

    (new ConsoleOutput())->writeln($output);
}
