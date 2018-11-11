<?php

namespace Acquia\Orca\Tasks;

use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\ProcessRunner;
use Symfony\Component\Finder\Finder;

/**
 * Provides a base task implementation.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 * @property \Symfony\Component\Finder\Finder $finder
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
abstract class TaskBase implements TaskInterface {

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Facade $facade
   *   The fixture.
   * @param \Symfony\Component\Finder\Finder $finder
   *   The finder.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Facade $facade, Finder $finder, ProcessRunner $process_runner) {
    $this->facade = $facade;
    $this->finder = $finder;
    $this->processRunner = $process_runner;
  }

}
