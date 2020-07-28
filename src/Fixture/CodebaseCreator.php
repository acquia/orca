<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Facade\ComposerFacade;
use Acquia\Orca\Facade\GitFacade;

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
   * The Git facade.
   *
   * @var \Acquia\Orca\Facade\GitFacade
   */
  private $git;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Facade\ComposerFacade $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Facade\GitFacade $git
   *   The Git facade.
   */
  public function __construct(ComposerFacade $composer, GitFacade $git) {
    $this->composer = $composer;
    $this->git = $git;
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
    $this->git->ensureFixtureRepo();
  }

}
