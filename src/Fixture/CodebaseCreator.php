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
   * @param \Acquia\Orca\Fixture\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Helper\Exception\OrcaException
   */
  public function create(FixtureOptions $options): void {
    $this->createProject($options);
    $this->git->ensureFixtureRepo();
  }

  /**
   * Creates the Composer project.
   *
   * @param \Acquia\Orca\Fixture\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Helper\Exception\OrcaException
   */
  private function createProject(FixtureOptions $options): void {
    if (!$options->hasSut()) {
      $this->composer->createProject($options);
      return;
    }

    /* @var \Acquia\Orca\Package\Package $sut */
    $sut = $options->getSut();
    if (!$sut->isProjectTemplate()) {
      $this->composer->createProject($options);
      return;
    }

    $this->composer->createProjectFromPackage($sut);
  }

}
