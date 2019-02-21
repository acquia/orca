<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;

/**
 * Resets the fixture.
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
   * Resets the fixture codebase and database.
   */
  public function reset(): void {
    $fixture_path = $this->fixture->getPath();
    $this->processRunner->git([
      'checkout',
      '--force',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ], $fixture_path);
    $this->processRunner->git(['clean', '--force', '-d'], $fixture_path);
  }

}
