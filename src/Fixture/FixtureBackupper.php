<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;

/**
 * Backs up the fixture.
 */
class FixtureBackupper {

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
   * Backs up the fixture codebase and database.
   */
  public function backup(): void {
    $this->processRunner->git(['add', '--all']);
    $this->processRunner->gitCommit('Backed up the fixture.');
    $this->processRunner->git([
      'tag',
      '--force',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ]);
  }

}
