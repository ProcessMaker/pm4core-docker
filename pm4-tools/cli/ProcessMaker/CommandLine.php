<?php

namespace ProcessMaker\Cli;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class CommandLine
{
    private $progress;

    private $time;

    public function __construct()
    {
        $this->time = microtime(true);
    }

    /**
     * Returns the timing (in seconds) since the
     * CommandLine class was instantiated
     *
     * @return string
     */
    public function timing(): string
    {
        $seconds = round(abs($this->time - microtime(true)), 2);
        $minutes = round($seconds / 60, 2);
        $hours = round($minutes / 60, 2);

        if ($hours >= 1.00) {
            return "$hours hours";
        }

        if ($minutes >= 1.00) {
            return "$minutes minutes";
        }

        return "$seconds seconds";
    }

    /**
     * Simple global function to run commands.
     *
     * @param  string  $command
     *
     * @return void
     */
    public function quietly(string $command)
    {
        $this->runCommand($command.' > /dev/null 2>&1');
    }

    /**
     * Simple global function to run commands.
     *
     * @param  string  $command
     *
     * @return void
     */
    public function quietlyAsUser(string $command)
    {
        $this->quietly('sudo -u "'.user().'" '.$command.' > /dev/null 2>&1');
    }

    public function transformCommandToRunAsUser(string $command, string $path = null): string
    {
        return ($path ? 'cd '.$path.' && ' : '').'sudo -u '.user().' '.$command;
    }

    /**
     * Pass the command to the command line and display the output.
     *
     * @param  string  $command
     *
     * @return void
     */
    public function passthru(string $command)
    {
        passthru($command);
    }

    /**
     * Create a ProgressBar bound to this class instance
     *
     * @param  int  $count
     * @param  string  $type
     */
    public function createProgressBar(int $count, string $type = 'minimal'): void
    {
        $this->progress = new ProgressBar(new ConsoleOutput, $count);

        ProgressBar::setFormatDefinition('message', '<info>%message%</info> (%percent%%)');
        ProgressBar::setFormatDefinition('minimal', 'Progress: %percent%%');

        $this->progress->setFormat($type);
        $this->progress->setRedrawFrequency(25);
        $this->progress->minSecondsBetweenRedraws(0.025);
        $this->progress->maxSecondsBetweenRedraws(0.05);
    }

    /**
     * @param  int|null  $count
     *
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    public function getProgress(int $count = null): ProgressBar
    {
        if (!$this->progress instanceof ProgressBar) {
            $this->createProgressBar($count);
        }

        return $this->progress;
    }

    /**
     * Run the given command as the non-root user.
     *
     * @param  string  $command
     * @param  callable|null  $onError
     * @param  string|null  $workingDir
     *
     * @return string
     */
    public function run(string $command, callable $onError = null, string $workingDir = null): string
    {
        return $this->runCommand($command, $onError, $workingDir);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable|null  $onError
     * @param  string|null  $workingDir
     *
     * @return string
     */
    public function runAsUser(string $command, callable $onError = null, string $workingDir = null): string
    {
        return $this->runCommand('sudo -u "'.user().'" '.$command, $onError, $workingDir);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable|null  $onError
     * @param  string|null  $workingDir
     *
     * @return string
     */
    public function runCommand(string $command, callable $onError = null, string $workingDir = null): string
    {
        info("Running Command: $command in $workingDir");
        $onError = $onError ? : function () {};

        if (method_exists(Process::class, 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($command, null, $_ENV);
        } else {
            $process = new Process($command, null, $_ENV);
        }

        if ($workingDir) {
            if (is_dir($workingDir) && !is_file($workingDir)) {
                $process->setWorkingDirectory($workingDir);
            }
        }

        $processOutput = '';

        // $process->setTimeout(null)->run(function ($type, $line) use (&$processOutput) {
        //     $processOutput .= $line;
        // });
        $process->setTimeout(null);
        $process->start();
        foreach ($process as $type => $data) {
            info($data);
            $processOutput .= $data;
        }

        if ($process->getExitCode() > 0) {
            $onError($process->getExitCode(), $processOutput);
        }

        return $processOutput;
    }

    public function mustRun($command, $path = null)
    {
        return $this->runCommand($command, function($code, $output) {
            throw new \Exception($output);
        }, $path);
    }
}
