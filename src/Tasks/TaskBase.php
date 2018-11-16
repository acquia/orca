<?php

namespace Acquia\Orca\Tasks;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Symfony\Component\Finder\Finder;

/**
 * Provides a base task implementation.
 *
 * @property \Symfony\Component\Finder\Finder $finder
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
abstract class TaskBase implements TaskInterface {

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Finder\Finder $finder
   *   The finder.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Finder $finder, Fixture $fixture, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->finder = $finder;
    $this->processRunner = $process_runner;
  }

}
