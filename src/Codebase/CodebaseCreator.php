<?php

namespace Acquia\Orca\Codebase;

use Acquia\Orca\Facade\ComposerFacade;

/**
 * Creates the codebase component of a fixture.
 */
class CodebaseCreator {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Facade\ComposerFacade
   */
  private $composer;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Facade\ComposerFacade $composer
   *   The Composer facade.
   */
  public function __construct(ComposerFacade $composer) {
    $this->composer = $composer;
  }

  /**
   * Creates the codebase.
   *
   * @param string $project_template_string
   *   The Composer project template string to use, optionally including a
   *   version constraint, e.g., "vendor/package" or "vendor/package:^1".
   * @param string $stability
   *   The stability flag, e.g., "alpha" or "dev".
   * @param string $directory
   *   The directory to create the project at.
   */
  public function create(string $project_template_string, string $stability, string $directory): void {
    $this->composer->createProject($project_template_string, $stability, $directory);
  }

}
