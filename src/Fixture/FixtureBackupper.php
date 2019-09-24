<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;

/**
 * Backs up the fixture.
 */
class FixtureBackupper {

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The fixture configurator.
   *
   * @var \Acquia\Orca\Fixture\FixtureConfigurator
   */
  private $fixtureConfigurator;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureConfigurator $fixture_configurator
   *   The fixture configurator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Fixture $fixture, FixtureConfigurator $fixture_configurator, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->fixtureConfigurator = $fixture_configurator;
    $this->processRunner = $process_runner;
  }

  /**
   * Backs up the fixture codebase and database.
   */
  public function backup(): void {
    $this->fixtureConfigurator->ensureGitConfig();
    $this->doGitBackup();
    $this->fixtureConfigurator->removeTemporaryLocalGitConfig();
  }

  /**
   * Performs the Git-based backup operation.
   */
  private function doGitBackup(): void {
    $fixture_path = $this->fixture->getPath();
    $this->processRunner->git(['add', '--all'], $fixture_path);
    $this->processRunner->gitCommit('Backed up the fixture.');
    $this->processRunner->git([
      'tag',
      '--force',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ], $fixture_path);
  }

}
