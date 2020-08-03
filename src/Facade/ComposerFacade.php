<?php

namespace Acquia\Orca\Facade;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Utility\ProcessRunner;
use InvalidArgumentException;

/**
 * Provides a facade for encapsulating Composer interactions.
 */
class ComposerFacade {

  /**
   * The fixture path.
   *
   * @var string
   */
  private $fixture;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, ProcessRunner $process_runner) {
    $this->fixture = $fixture_path_handler->getPath();
    $this->processRunner = $process_runner;
  }

  /**
   * Creates the Composer project.
   *
   * @param string $project_template_string
   *   The Composer project template string to use, optionally including a
   *   version constraint, e.g., "vendor/package" or "vendor/package:^1".
   * @param string $stability
   *   The stability flag, e.g., "alpha" or "dev".
   * @param string $directory
   *   The directory to create the project at.
   */
  public function createProject(string $project_template_string, string $stability, string $directory): void {
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'create-project',
      '--no-dev',
      '--no-scripts',
      '--no-install',
      '--no-interaction',
      "--stability={$stability}",
      $project_template_string,
      $directory,
    ]);
  }

  /**
   * Removes packages.
   *
   * @param string[] $packages
   *   A list of package machine names, e.g., "vendor/package".
   */
  public function removePackages(array $packages): void {
    if (empty($packages)) {
      throw new InvalidArgumentException('No packages provided to remove.');
    }
    $this->runComposer([
      'remove',
      '--no-update',
    ], $packages);
  }

  /**
   * Requires packages.
   *
   * @param string[] $packages
   *   A list of package machine names, e.g., "vendor/package".
   */
  public function requirePackages(array $packages): void {
    if (empty($packages)) {
      throw new InvalidArgumentException('No packages provided to remove.');
    }
    $this->runComposer([
      'require',
      '--no-interaction',
    ], $packages);
  }

  /**
   * Updates composer.lock.
   */
  public function updateLockFile(): void {
    $this->runComposer([
      'update',
      '--lock',
    ]);
  }

  /**
   * Dispatches a command to Composer.
   *
   * @param string[] $command
   *   A list of command parts, e.g., ['require', '--no-interaction'].
   * @param string[] $args
   *   A list of of command arguments, e.g., ['vendor/package'].
   */
  private function runComposer(array $command, array $args = []): void {
    array_unshift($command, 'composer');
    $command = array_merge($command, $args);
    $this->processRunner
      ->runOrcaVendorBin($command, $this->fixture);
  }

}
