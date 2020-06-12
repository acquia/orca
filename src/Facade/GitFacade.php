<?php

namespace Acquia\Orca\Facade;

use Acquia\Orca\Utility\ProcessRunner;

/**
 * Provides a facade for encapsulating Git interactions.
 */
class GitFacade {

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

}
