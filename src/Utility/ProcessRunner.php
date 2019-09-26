<?php

namespace Acquia\Orca\Utility;

use Acquia\Orca\Fixture\Fixture;
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
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  private $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Fixture $fixture, SymfonyStyle $output, string $project_dir) {
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
   * @param array $command
   *   An array of command parts, where the first element is an executable name.
   * @param string|null $cwd
   *   The working directory, or NULL to use the working dir of the current PHP
   *   process.
   *
   * @return int
   *   The exit status code.
   */
  public function runExecutable(array $command, ?string $cwd = NULL): int {
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

    if (is_null($command[0])) {
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
   * Runs a given command in ORCA's vendor/bin directory.
   *
   * @param array $command
   *   An array of command parts, where the first element is a vendor binary
   *   name.
   * @param string|null $cwd
   *   The working directory, or NULL to use the working dir of the current PHP
   *   process.
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
    $command[0] = "{$this->projectDir}/vendor/bin/{$command[0]}";
    return $this->createVendorBinProcess($command);
  }

  /**
   * Executes a Git command against the fixture.
   *
   * @param array $args
   *   An array of Git command arguments.
   * @param string|null $cwd
   *   The working directory, or NULL to use the working dir of the current PHP
   *   process.
   *
   * @return int
   *   The exit status code.
   */
  public function git(array $args, ?string $cwd = NULL): int {
    $cwd = $cwd ?? $this->fixture->getPath();
    $command = $args;
    array_unshift($command, 'git');
    return $this->runExecutable($command, $cwd);
  }

  /**
   * Executes a Git `commit` command against the fixture.
   *
   * @param string $message
   *   The commit message.
   *
   * @return int
   *   The exit status code.
   */
  public function gitCommit(string $message): int {
    return $this->git([
      'commit',
      "--message={$message}",
      '--quiet',
      '--allow-empty',
    ]);
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
