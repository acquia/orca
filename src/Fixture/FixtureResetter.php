<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Git\GitFacade;
use Acquia\Orca\Utility\ProcessRunner;

/**
 * Resets the fixture.
 */
class FixtureResetter {

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ProcessRunner $process_runner) {
    $this->processRunner = $process_runner;
  }

  /**
   * Resets the fixture codebase and database.
   */
  public function reset(): void {
    $this->processRunner->git([
      'checkout',
      '--force',
      GitFacade::FRESH_FIXTURE_TAG,
    ]);
    $this->processRunner->git(['clean', '--force', '-d']);
  }

}
