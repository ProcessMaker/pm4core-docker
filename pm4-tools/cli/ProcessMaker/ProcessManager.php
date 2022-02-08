<?php

namespace ProcessMaker\Cli;

use LogicException;
use React\ChildProcess\Process;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Output\ConsoleOutput;

class ProcessManager
{
    private $processCollections, $outputCollection, $finalCallback, $cli;

    public $verbose = false;

    public function __construct(
        CommandLine $cli,
        Collection $outputCollection,
        Collection $processCollections)
    {
        $this->cli = $cli;
        $this->processCollections = $processCollections;
        $this->outputCollection = $outputCollection;
    }

    public function setVerbosity(bool $verbose)
    {
        $this->verbose = $verbose;
    }

    public function getProcessOutput(string $key = null): Collection
    {
        return $key ? $this->outputCollection->get($key) : $this->outputCollection;
    }

    public function addProcessOutput(string $key, $output)
    {
        if (!$this->getProcessOutput()->has($key)) {
             $this->getProcessOutput()->put($key, new Collection);
        }

        $this->getProcessOutput($key)->push($output);
    }

    public function findProcessExitCode(string $key): int
    {
        if (!$output = $this->getProcessOutput($key)) {
            return 1;
        }

        // Search through the output for the array
        // containing the exit code value
        $exitCode = $output->reject(function ($line) {
            return ! is_array($line) || ! array_key_exists('exit_code', $line);
        });

        // If we can't find it, assume the process
        // exited with a general error
        if ($exitCode->isNotEmpty()) {
            return $exitCode->flatten()->first() ?? 1;
        }

        return 1;
    }

    /**
     * @param  string  $key
     *
     * @return Collection
     */
    public function getProcessCollections(string $key): Collection
    {
        if (!$this->processCollections->get($key) instanceof Collection) {
             $this->processCollections->put($key, new Collection());
        }

        return $this->processCollections->get($key);
    }

    /**
     * @param  array  $commands
     */
    public function buildProcessesBundleAndStart(array $commands)
    {
        $this->startProcessesBundle($this->buildProcessesBundle($commands));
    }

    /**
     * @param  array  $commands
     *
     * @return \Illuminate\Support\Collection
     */
    public function buildProcessesBundle(array $commands): Collection
    {
        $commands = array_filter($commands, function ($command) {
            return !is_string($command);
        });

        if (blank($commands)) {
            throw new LogicException('Commands array cannot be empty');
        }

        $bundles = collect($commands)->transform(function (array $set) {
            return collect(array_map(function ($command) {
                return new Process($command);
            }, $set));
        });

        return $this->setExitListeners($bundles);
    }

    /**
     * @param  \Illuminate\Support\Collection  $bundles
     *
     * @return \Illuminate\Support\Collection
     */
    private function setExitListeners(Collection $bundles): Collection
    {
        // Set each Process to start the next process
        // in the bundle when it exists
        $bundles->each(function (Collection $bundle) {
            return $bundle->transform(function (Process $process, $index) use (&$bundle) {

                $process->on('exit', function ($exitCode, $termSignal) use (&$bundle, $process, $index) {

                    if (!$this->verbose) {
                        $this->cli->getProgress()->advance();
                    }

                    // Add to the "exited" process collection
                    $this->getProcessCollections('exited')->push($process);

                    // Get the info we need to output to stdout
                    $pid = $process->getPid();
                    $command = $process->getCommand();

                    if ($this->verbose) {
                        if ($exitCode === 0) {
                            output("<fg=cyan>$pid</>: <info>$command</info>");
                        } else {
                            output("<fg=cyan>$pid</>: <fg=red>$command</>");
                        }
                    }

                    // Add to the process collections
                    if ($exitCode === 0) {
                        $this->getProcessCollections('successful')->push($process);
                    } else {
                        $this->getProcessCollections('errors')->push($process);
                    }

                    // Add to the processOutput property for reading later
                    $this->addProcessOutput($process->getCommand(), ['exit_code' => $exitCode]);

                    // Find the next process to run
                    $next_process = $bundle->get($index + 1);

                    // If one exists, run it
                    if ($next_process instanceof Process) {
                        $this->startProcessAndPipeOutput($next_process);
                    }

                    $queued = $this->getProcessCollections('queued')->count();
                    $exited = $this->getProcessCollections('exited')->count();

                    // All processes are finished
                    if ($queued === $exited) {
                        // Keeps the stdout clean during verbose mode
                        if (!$this->verbose) {
                            $this->cli->getProgress()->finish();
                        }

                        // Last but not least, run the bound callback
                        $this->getFinalCallback();
                    }
                });

                return $process;
            });
        });

        return $bundles;
    }

    public function getFinalCallback()
    {
        if (is_callable($this->finalCallback)) {
            return call_user_func($this->finalCallback);
        }
    }

    public function setFinalCallback(callable $callback)
    {
        $this->finalCallback = $callback;
    }

    /**
     * @param  \Illuminate\Support\Collection  $bundle
     *
     * @return \Illuminate\Support\Collection
     */
    private function validateBundle(Collection $bundle): Collection
    {
        return $bundle->reject(function ($process) {
            return ! $process instanceof Process;
        });
    }

    /**
     * @param  \Illuminate\Support\Collection  $bundles
     */
    public function startProcessesBundle(Collection $bundles): void
    {
        $bundles->transform(function (Collection $bundle) {
            return $this->validateBundle($bundle);
        });

        if ($bundles->isEmpty()) {
            throw new LogicException('No bundles of Processes found');
        }

        $this->setProcessIndexes($bundles);

        $this->getStartProcesses($bundles)->each(function (Process $process) {
            $this->startProcessAndPipeOutput($process);
        });
    }

    /**
     * @param  \Illuminate\Support\Collection  $bundles
     *
     * @return \Illuminate\Support\Collection
     */
    private function getStartProcesses(Collection $bundles): Collection
    {
        return $this->validateBundle(
            $bundles->map(function (Collection $bundle) {
                return $bundle->first();
            })
        );
    }

    /**
     * @param  \Illuminate\Support\Collection  $bundles
     *
     * @return void
     */
    private function setProcessIndexes(Collection $bundles): void
    {
        $index = 0;
        $total_processes = 0;

        // Count up all of the processes
        $bundles->each(function ($bundle) use (&$total_processes) {
            $total_processes += $bundle->count();
        });

        // Set the "process_index" property for each
        // process among each bundle
        $bundles->each(function ($bundle) use (&$index) {
            $bundle->transform(function (Process $process) use (&$index) {
                $process->index = $index++;

                $this->getProcessCollections('queued')->push($process);

                return $process;
            });
        });

        if (!$this->verbose) {
            $this->cli->getProgress($total_processes);
        }
    }

    /**
     * @param  \React\ChildProcess\Process  $process
     */
    private function startProcessAndPipeOutput(Process $process): void
    {
        if ($process->isRunning()) {
            return;
        }

        $process->start();

        $process->stdout->on('data', function ($output) use (&$process) {
            $this->addProcessOutput($process->getCommand(), $output);
        });

        $this->getProcessCollections('started')->push($process);
    }
}
