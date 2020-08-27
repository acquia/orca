<?php

namespace Acquia\Orca\Composer;

use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Exception\FileNotFoundException as OrcaFileNotFoundException;
use Acquia\Orca\Helper\Exception\OrcaException;
use Acquia\Orca\Helper\Exception\ParseError;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Composer\Package\Version\VersionGuesser;
use Exception;
use InvalidArgumentException;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundExceptionAlias;
use Noodlehaus\Exception\ParseException;

/**
 * Provides a facade for encapsulating Composer interactions.
 */
class Composer {

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * The fixture path.
   *
   * @var string
   */
  private $fixture;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * The version guesser.
   *
   * @var \Composer\Package\Version\VersionGuesser
   */
  private $versionGuesser;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   * @param \Composer\Package\Version\VersionGuesser $version_guesser
   *   The version guesser.
   */
  public function __construct(ConfigLoader $config_loader, FixturePathHandler $fixture_path_handler, ProcessRunner $process_runner, VersionGuesser $version_guesser) {
    $this->configLoader = $config_loader;
    $this->fixture = $fixture_path_handler->getPath();
    $this->processRunner = $process_runner;
    $this->versionGuesser = $version_guesser;
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
   * Guesses the version of a local package.
   *
   * @param string $path
   *   The path to the package to guess.
   *
   * @return string
   *   The guessed version string.
   *
   * @throws \Acquia\Orca\Helper\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Helper\Exception\OrcaException
   * @throws \Acquia\Orca\Helper\Exception\ParseError
   */
  public function guessVersion(string $path): string {
    try {
      $composer_json_path = "{$path}/composer.json";
      $package_config = $this->configLoader
        ->load($composer_json_path)
        ->all();
    }
    catch (NoodlehausFileNotFoundExceptionAlias $e) {
      throw new OrcaFileNotFoundException("No such file: {$composer_json_path}");
    }
    catch (ParseException $e) {
      throw new ParseError("Cannot parse {$composer_json_path}");
    }
    catch (Exception $e) {
      throw new OrcaException("Unknown error guessing version at {$path}");
    }

    $guess = $this->versionGuesser
      ->guessVersion($package_config, $path);
    return (empty($guess['version'])) ? '@dev' : $guess['version'];
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
      throw new InvalidArgumentException('No packages provided to require.');
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
