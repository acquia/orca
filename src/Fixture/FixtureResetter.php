<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Resets a fixture.
 */
class FixtureResetter {

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Fixture $fixture, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->processRunner = $process_runner;
  }

  /**
   * Resets the fixture.
   */
  public function reset(): void {
    $this->resetCodeAndDatabase();
    $this->deleteUploadedFiles();
  }

  /**
   * Resets the codebase and the database.
   */
  private function resetCodeAndDatabase(): void {
    $this->git(['checkout', '--force', Fixture::FRESH_FIXTURE_GIT_TAG]);
    $this->git(['clean', '--force', '-d']);
  }

  /**
   * Deletes all uploaded files.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function deleteUploadedFiles(): void {
    (Process::fromShellCommandline(implode(' ', array_merge([
      (new ExecutableFinder())->find('git'),
      'clean',
      '--force',
      '-dx',
    ], $this->fixture->getFileUploadDirs()))))
      ->setWorkingDirectory($this->fixture->getPath())
      ->run();
  }

  /**
   * Executes a Git command against the fixture.
   *
   * @param array $args
   *   An array of Git command arguments.
   */
  private function git(array $args): void {
    $command = $args;
    array_unshift($command, 'git');
    $this->processRunner
      ->createExecutableProcess($command)
      ->setWorkingDirectory($this->fixture->getPath())
      ->run();
  }

}
