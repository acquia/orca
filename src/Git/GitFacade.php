<?php

namespace Acquia\Orca\Git;

use Acquia\Orca\Utility\ProcessRunner;

/**
 * Provides a facade for encapsulating Git interactions against the fixture.
 */
class GitFacade {

  public const FRESH_FIXTURE_TAG = 'fresh-fixture';

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
   * Ensures the fixture's Git repository existence and basic configuration.
   */
  public function ensureFixtureRepo(): void {
    // Ensure the repository is initialized.
    $this->git(['init']);
    // Prevent "Please tell me who you are" errors.
    $this->git(['config', 'user.name', 'ORCA']);
    $this->git(['config', 'user.email', 'no-reply@acquia.com']);
  }

  /**
   * Executes a Git command.
   *
   * @param string[] $command
   *   An array of command-line arguments.
   */
  private function git(array $command): void {
    $this->processRunner->git($command);
  }

  /**
   * Backs up the fixture state.
   *
   * The SQLite database will be included, if present.
   */
  public function backupFixtureState(): void {
    $this->ensureFixtureRepo();
    $this->processRunner->git(['add', '--all']);
    $this->processRunner->gitCommit('Backed up the fixture.');
    $this->processRunner->git([
      'tag',
      '--force',
      self::FRESH_FIXTURE_TAG,
    ]);
  }

}
