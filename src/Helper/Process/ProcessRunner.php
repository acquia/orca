<?php

namespace Acquia\Orca\Helper\Process;

use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs processes.
 */
class ProcessRunner {

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, SymfonyStyle $output, OrcaPathHandler $orca_path_handler) {
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->output = $output;
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
   *
   * @throws \Symfony\Component\Process\Exception\ProcessFailedException
   *   If the process is unsuccessful.
   */
  public function run(Process $process, ?string $cwd = NULL): int {
    $this->output->writeln(sprintf('> %s', $process->getCommandLine()));

    if ($cwd) {
      $process->setWorkingDirectory($cwd);
    }

    $status = $process
      ->setTimeout(NULL)
      ->setIdleTimeout(NULL)
      ->run(function () {
        // Write process buffer to output.
        $buffer = func_get_arg(1);
        $this->output->write($buffer);
      });

    if (!$process->isSuccessful()) {
      $process->disableOutput();
      throw new ProcessFailedException($process);
    }

    $this->output->newLine();

    return $status;
  }

  /**
   * Runs a given executable command.
   *
   * @param string $exe
   *   The executable command name.
   * @param array $args
   *   An array of command arguments.
   * @param string|null $cwd
   *   The working directory, or NULL to use the fixture directory.
   *
   * @return int
   *   The exit status code.
   */
  public function runExecutable(string $exe, array $args, ?string $cwd = NULL): int {
    $cwd = $cwd ?? $this->fixture->getPath();
    $command = array_merge([$exe], $args);
    $process = $this->createExecutableProcess($command);
    return $this->run($process, $cwd);
  }

  /**
   * Creates a process for a given executable command.
   *
   * @param array $command
   *   An array of command parts, where the first element is an executable name.
   *
   * @return \Symfony\Component\Process\Process
   *   The created process.
   */
  public function createExecutableProcess(array $command): Process {
    $command[0] = (new ExecutableFinder())->find($command[0]);

    if ($command[0] === NULL) {
      throw new RuntimeException(sprintf('Could not find executable: %s.', $command[0]));
    }

    return new Process($command);
  }

  /**
   * Runs a given command in the fixture vendor/bin directory.
   *
   * @param array $command
   *   An array of command parts, where the first element is a vendor binary
   *   name.
   *
   * @return int
   *   The exit status code.
   */
  public function runFixtureVendorBin(array $command): int {
    $process = $this->createFixtureVendorBinProcess($command);
    return $this->run($process, $this->fixture->getPath());
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
    $command[0] = $this->fixture->getPath("vendor/bin/{$command[0]}");
    return $this->createVendorBinProcess($command);
  }

  /**
   * Runs an ORCA command.
   *
   * @param array $command
   *   An array of command arguments.
   *
   * @return int
   *   The exit status code.
   */
  public function runOrca(array $command): int {
    array_unshift($command, $this->orca->getPath('bin/orca'));
    $process = new Process($command);
    $process->setTty(Process::isTtySupported());
    $cwd = $this->orca->getPath();
    return $this->run($process, $cwd);
  }

  /**
   * Runs a given command in ORCA's vendor/bin directory.
   *
   * @param array $command
   *   An array of command parts, where the first element is a vendor binary
   *   name.
   * @param string|null $cwd
   *   The working directory, or NULL to use the fixture directory.
   *
   * @return int
   *   The exit status code.
   */
  public function runOrcaVendorBin(array $command, ?string $cwd = NULL): int {
    $process = $this->createOrcaVendorBinProcess($command);
    return $this->run($process, $cwd);
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
    $command[0] = $this->orca->getPath("vendor/bin/{$command[0]}");
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
