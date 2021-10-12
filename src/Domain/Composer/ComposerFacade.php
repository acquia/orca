<?php

namespace Acquia\Orca\Domain\Composer;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\FixtureOptions;
use Composer\Package\Loader\ValidatingArrayLoader;
use Composer\Semver\VersionParser;

/**
 * Provides a facade for Composer.
 */
class ComposerFacade {

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Options\FixtureOptions
   */
  private $options;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca
   *   The ORCA path handler.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca, PackageManager $package_manager, ProcessRunner $process_runner) {
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca;
    $this->packageManager = $package_manager;
    $this->processRunner = $process_runner;
  }

  /**
   * Creates the Composer project.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   */
  public function createProject(FixtureOptions $options): void {
    $this->options = $options;

    $stability = 'alpha';
    if ($options->isDev()) {
      $stability = 'dev';
    }

    $this->processRunner->runOrcaVendorBin([
      'composer',
      'create-project',
      "--stability={$stability}",
      '--no-dev',
      '--no-scripts',
      '--no-progress',
      '--no-install',
      '--no-interaction',
      $this->getProjectTemplateString(),
      $this->fixture->getPath(),
    ], $this->orca->getPath());
  }

  /**
   * Gets the project template string for requiring with Composer.
   *
   * @return string
   *   The project template string.
   */
  private function getProjectTemplateString(): string {
    $project_template = $this->options->getProjectTemplate();
    $sut = $this->options->getSut();

    // The project template is the SUT.
    if ($sut && $sut->isProjectTemplate()) {
      return $this->options->getProjectTemplate();
    }

    // The project template is BLT project.
    if ($project_template === 'acquia/blt-project') {
      return $this->getBltProjectTemplateString();
    }

    return $project_template;
  }

  /**
   * Gets the project template string for BLT project.
   *
   * @return string
   *   The project template string.
   */
  private function getBltProjectTemplateString(): string {
    $project_template = $this->options->getProjectTemplate();
    $blt = $this->packageManager->getBlt();
    if ($this->options->isDev()) {
      return $project_template;
    }
    return $project_template . ':' . $blt->getVersionRecommended($this->options->getCore());
  }

  /**
   * Creates a project from a given package's local path repository.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package in question.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function createProjectFromPackage(Package $package): void {
    $repository = json_encode([
      'type' => 'path',
      'url' => $package->getRepositoryUrlAbsolute(),
      'options' => [
        'symlink' => FALSE,
        'canonical' => TRUE,
      ],
    ]);
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'create-project',
      '--stability=dev',
      "--repository={$repository}",
      '--no-dev',
      '--no-scripts',
      '--no-install',
      '--no-interaction',
      $package->getPackageName(),
      $this->fixture->getPath(),
    ], $this->orca->getPath());
  }

  /**
   * Determines whether or not a given version constraint is valid.
   *
   * @param string $version
   *   The version to test.
   *
   * @return bool
   *   TRUE if it is valid or FALSE if not.
   */
  public static function isValidVersionConstraint(string $version): bool {
    try {
      $parser = new VersionParser();
      $parser->parseConstraints($version);
    }
    catch (\UnexpectedValueException $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Determines whether or not a given name is a valid Composer package name.
   *
   * @param string $name
   *   The name to test.
   *
   * @return bool
   *   TRUE if it's valid or FALSE if not.
   */
  public static function isValidPackageName(string $name): bool {
    return !ValidatingArrayLoader::hasPackageNamingError($name);
  }

  /**
   * Removes packages.
   *
   * @param string[] $packages
   *   A list of package machine names, e.g., "vendor/package".
   */
  public function removePackages(array $packages): void {
    if (empty($packages)) {
      throw new \InvalidArgumentException('No packages provided to remove.');
    }
    $this->runComposer([
      'remove',
    ], $packages);
  }

  /**
   * Requires packages.
   *
   * @param string[] $packages
   *   A list of package machine names, e.g., "vendor/package".
   * @param bool|null $prefer_source
   *   TRUE to pass the --prefer-source option or FALSE not to.
   * @param bool|null $no_update
   *   TRUE to pass the --no-update or FALSE not to.
   */
  public function requirePackages(array $packages, ?bool $prefer_source = FALSE, ?bool $no_update = FALSE): void {
    $command = ['require'];
    if ($prefer_source) {
      $command[] = '--prefer-source';
    }
    $command[] = '--no-progress';
    if ($no_update) {
      $command[] = '--no-update';
    }
    $command[] = '--no-interaction';
    $command[] = '--with-all-dependencies';

    $this->runComposer($command, $packages);
  }

  /**
   * Updates composer.lock.
   */
  public function updateLockFile(): void {
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'update',
        '--lock',
      ], $this->fixture->getPath());
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
    $command = array_merge($command, $args);
    array_unshift($command, 'composer');
    $this->processRunner->runOrcaVendorBin($command, $this->fixture->getPath());
  }

}
