<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Configures a fixture.
 */
class FixtureConfigurator {

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
   * Ensures the necessary Git user configuration.
   *
   * Prevents "Please tell me who you are" errors.
   */
  public function ensureGitConfig(): void {
    $this->git(['init']);
    $this->ensureGitConfigHasValue('user.name', 'ORCA');
    $this->ensureGitConfigHasValue('user.email', 'no-reply@acquia.com');
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
   * Ensure that the given Git configuration has a value.
   *
   * @param string $name
   *   The configuration name, e.g., "user.name".
   * @param string $default_value
   *   The value to set it to if it's empty.
   */
  private function ensureGitConfigHasValue(string $name, string $default_value): void {
    try {
      $this->git([
        'config',
        $name,
      ]);
    }
    catch (ProcessFailedException $e) {
      $this->git([
        'config',
        $name,
        $default_value,
      ]);
    }
  }

  /**
   * Remove the temporary local Git user config.
   *
   * This is irrelevant in a CI context, but in case someone uses ORCA to create
   * a fixture for other purposes, this prevents them from mistakenly
   * attributing their own commits to ORCA.
   */
  public function removeTemporaryLocalGitConfig(): void {
    $values = [
      'user.name',
      'user.email',
    ];
    foreach ($values as $value) {
      try {
        $this->git([
          'config',
          '--local',
          '--unset',
          $value,
        ]);
      }
      catch (ProcessFailedException $e) {
        // Git returns a non-zero status code (5) if asked to unset a
        // configuration value that isn't set, which isn't a problem scenario in
        // this case. Ignore.
      }
    }
  }

}
