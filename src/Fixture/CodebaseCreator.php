<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Git\Git;

/**
 * Creates the codebase component of a fixture.
 */
class CodebaseCreator {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Composer\Composer
   */
  private $composer;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Git\Git
   */
  private $git;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Fixture\FixtureOptions
   */
  private $options;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Composer\Composer $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Git\Git $git
   *   The Git facade.
   */
  public function __construct(Composer $composer, Git $git) {
    $this->composer = $composer;
    $this->git = $git;
  }

  /**
   * Creates the codebase.
   *
   * @param \Acquia\Orca\Fixture\FixtureOptions $fixture_options
   *   The fixture options.
   * @param string $project_template_string
   *   The Composer project template string to use, optionally including a
   *   version constraint, e.g., "vendor/package" or "vendor/package:^1".
   */
  public function create(FixtureOptions $fixture_options, string $project_template_string): void {
    $this->options = $fixture_options;
    $this->createProject($project_template_string);
    $this->git->ensureFixtureRepo();
  }

  /**
   * Creates the Composer project.
   *
   * @param string $project_template_string
   *   The project tempalte string.
   */
  private function createProject(string $project_template_string): void {
    $stability = 'alpha';
    if ($this->options->isDev()) {
      $stability = 'dev';
    }
    $this->composer->createProject($project_template_string, $stability);
  }

}
