<?php

namespace Acquia\Orca\Facade;

use Acquia\Orca\Utility\ProcessRunner;

/**
 * Provides a facade for encapsulating Composer interactions.
 */
class ComposerFacade {

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
   * Creates the Composer project.
   *
   * @param string $project_template_string
   *   The Composer project template string to use, optionally including a
   *   version constraint, e.g., "vendor/package" or "vendor/package:^1".
   * @param string $stability
   *   The stability flag, e.g., "alpha" or "dev".
   * @param string $directory
   *   The directory to create the project at.
   */
  public function createProject(string $project_template_string, string $stability, string $directory): void {
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'create-project',
      '--no-dev',
      '--no-scripts',
      '--no-install',
      '--no-interaction',
      "--stability={$stability}",
      $project_template_string,
      $directory,
    ]);
  }

}
