<?php

namespace Acquia\Orca\Tasks;

use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\ProcessRunner;

/**
 * Provides a base task implementation.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
abstract class TaskBase implements TaskInterface {

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Facade $facade
   *   The fixture.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Facade $facade, ProcessRunner $process_runner) {
    $this->facade = $facade;
    $this->processRunner = $process_runner;
  }

}
