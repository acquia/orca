<?php

namespace Acquia\Orca;

use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs processes.
 *
 * @property \Symfony\Component\Process\ExecutableFinder $executableFinder
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Symfony\Component\Console\Style\SymfonyStyle $output
 * @property string $projectDir
 */
class ProcessRunner {

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Process\ExecutableFinder $executable_finder
   *   An executable finder.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(ExecutableFinder $executable_finder, Fixture $fixture, SymfonyStyle $output, string $project_dir) {
    $this->executableFinder = $executable_finder;
    $this->fixture = $fixture;
    $this->output = $output;
    $this->projectDir = $project_dir;
  }

  /**
   * Runs a given process.
   *
   * @param \Symfony\Component\Process\Process $process
   *   The process to run.
   * @param string|null $cwd
   *   The working directory, or NULL to use the working dir of the current PHP
   *   process.
   *
   * @return int
   *   The exit status code.
   */
  public function run(Process $process, ?string $cwd = NULL): int {
    $this->output->writeln(sprintf('> %s', $process->getCommandLine()));

    if ($cwd) {
      $process->setWorkingDirectory($cwd);
    }

    // Write process buffer to output.
    $status = $process->setTimeout(0)->run(function () {
      $buffer = func_get_arg(1);
      $this->output->write($buffer);
    });

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $this->output->newLine();

    return $status;
  }

  /**
   * Creates a process for a given executable command.
   *
   * @param array $command
   *   An array of command parts, where the first element is an executable name.
   *
   * @return \Symfony\Component\Process\Process
   */
  public function createExecutableProcess(array $command): Process {
    $command[0] = $this->executableFinder->find($command[0]);

    if (is_null($command[0])) {
      throw new RuntimeException(sprintf('Could not find executable: %s.', $command[0]));
    }

    return new Process($command);
  }

  /**
   * Creates a process for a given command in the fixture vendor/bin directory.
   *
   * @param array $command
   *   An array of command parts, where the first element is a vendor binary
   *   name.
   *
   * @return \Symfony\Component\Process\Process
   *   The created process.
   */
  public function createFixtureVendorBinProcess(array $command): Process {
    $command[0] = $this->fixture->rootPath("vendor/bin/{$command[0]}");
    return $this->createVendorBinProcess($command);
  }

  /**
   * Creates a process for a given command in ORCA's vendor/bin directory.
   *
   * @param array $command
   *   An array of command parts, where the first element is a vendor binary
   *   name.
   *
   * @return \Symfony\Component\Process\Process
   *   The created process.
   */
  public function createOrcaVendorBinProcess(array $command): Process {
    $command[0] = "{$this->projectDir}/vendor/bin/{$command[0]}";
    return $this->createVendorBinProcess($command);
  }

  /**
   * Creates a process for a given vendor binary command.
   *
   * @param array $command
   *   An array of command parts, where the first element is a vendor binary
   *   name.
   *
   * @return \Symfony\Component\Process\Process
   *   The created process.
   */
  protected function createVendorBinProcess(array $command): Process {
    if (!file_exists($command[0])) {
      throw new RuntimeException(sprintf('Could not find vendor binary: %s.', $command[0]));
    }

    return new Process($command);
  }

}
