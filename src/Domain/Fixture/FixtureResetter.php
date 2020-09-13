<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Git\Git;
use Acquia\Orca\Helper\Process\ProcessRunner;

/**
 * Resets the fixture.
 */
class FixtureResetter {

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
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
      Git::FRESH_FIXTURE_TAG,
    ]);
    $this->processRunner->git(['clean', '--force', '-d']);
  }

}
