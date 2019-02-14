<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;

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
   * Resets the fixture codebase and the database.
   */
  public function reset(): void {
    $this->git(['checkout', '--force', Fixture::FRESH_FIXTURE_GIT_TAG]);
    $this->git(['clean', '--force', '-d']);
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
