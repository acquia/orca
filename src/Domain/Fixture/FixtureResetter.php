<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Git\GitFacade;

/**
 * Resets the fixture.
 */
class FixtureResetter {

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Domain\Git\GitFacade
   */
  private $git;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Git\GitFacade $git
   *   The Git facade.
   */
  public function __construct(GitFacade $git) {
    $this->git = $git;
  }

  /**
   * Resets the fixture codebase and database.
   */
  public function reset(): void {
    $this->git->resetRepoState();
  }

}
