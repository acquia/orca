<?php

namespace Acquia\Orca\Domain\Git;

use Acquia\Orca\Helper\Process\ProcessRunner;

/**
 * Provides a facade for Git.
 */
class GitFacade {

  public const FRESH_FIXTURE_TAG = 'fresh-fixture';

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ProcessRunner $process_runner) {
    $this->processRunner = $process_runner;
  }

  /**
   * Backs up the fixture repository state.
   *
   * The SQLite database will be included, if present.
   */
  public function backupFixtureRepo(): void {
    $this->ensureFixtureRepo();
    $this->processRunner->git(['add', '--all']);
    $this->processRunner->gitCommit('Backed up the fixture.');
    $this->processRunner->git([
      'tag',
      '--force',
      self::FRESH_FIXTURE_TAG,
    ]);
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
   * Resets the fixture's Git repository state.
   */
  public function resetRepoState(): void {
    $this->processRunner->git([
      'checkout',
      '--force',
      self::FRESH_FIXTURE_TAG,
    ]);
    $this->processRunner->git(['clean', '--force', '-d']);
  }

}
