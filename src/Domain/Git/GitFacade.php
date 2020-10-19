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
   * Executes a Git command.
   *
   * @param array $args
   *   An array of Git command arguments.
   * @param string|null $cwd
   *   The working directory, or NULL to use the fixture directory.
   *
   * @return int
   *   The exit status code.
   */
  public function execute(array $args, ?string $cwd = NULL): int {
    return $this->processRunner->runExecutable('git', $args, $cwd);
  }

  /**
   * Backs up the fixture repository state.
   *
   * The SQLite database will be included, if present.
   */
  public function backupFixtureRepo(): void {
    $this->ensureFixtureRepo();
    $this->execute(['add', '--all']);
    $this->execute([
      'commit',
      "--message=Backed up the fixture.",
      '--quiet',
      '--allow-empty',
    ]);
    $this->execute([
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
    $this->execute(['init']);
    // Prevent "Please tell me who you are" errors.
    $this->execute(['config', 'user.name', 'ORCA']);
    $this->execute(['config', 'user.email', 'no-reply@acquia.com']);
  }

  /**
   * Resets the fixture's Git repository state.
   */
  public function resetRepoState(): void {
    $this->execute([
      'checkout',
      '--force',
      self::FRESH_FIXTURE_TAG,
    ]);
    $this->execute(['clean', '--force', '-d']);
  }

}
