<?php

namespace Acquia\Orca\Task\DeprecatedCodeScanner;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Scans for deprecated code with PhpStan.
 */
class PhpStanTask {

  use SutSettingsTrait;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The "scan contrib" flag.
   *
   * @var bool
   */
  private $scanContrib = FALSE;

  /**
   * The SUT to scan.
   *
   * @var \Acquia\Orca\Fixture\Package|null
   */
  private $sut;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Filesystem $filesystem, Fixture $fixture, PackageManager $package_manager, ProcessRunner $process_runner) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture;
    $this->packageManager = $package_manager;
    $this->processRunner = $process_runner;
  }

  /**
   * Executes the test.
   *
   * @return int
   *   The exit status code.
   */
  public function execute(): int {
    try {
      $command = [
        'phpstan',
        'analyse',
        sprintf('--configuration=%s/phpstan.neon', __DIR__),
      ];
      if ($this->sut) {
        $command[] = $this->sut->getInstallPathAbsolute();
      }
      if ($this->scanContrib) {
        $command[] = $this->getAndEnsurePath('docroot/modules/contrib');
        $command[] = $this->getAndEnsurePath('docroot/profiles/contrib');
        $command[] = $this->getAndEnsurePath('docroot/themes/contrib');
      }
      $this->processRunner->runFixtureVendorBin($command);
    }
    catch (ProcessFailedException $e) {
      return StatusCodes::ERROR;
    }
    return StatusCodes::OK;
  }

  /**
   * Gets a fixture path and ensures its presence.
   *
   * @param string $path
   *   The path to ensure.
   *
   * @return string
   *   The absolute path.
   */
  private function getAndEnsurePath(string $path): string {
    $absolute_path = $this->fixture->getPath($path);
    $this->filesystem->mkdir($absolute_path);
    return $absolute_path;
  }

  /**
   * Sets the "scan contrib" flag.
   *
   * @param bool $scan_contrib
   *   TRUE to scan contrib or FALSE not to.
   */
  public function setScanContrib(bool $scan_contrib): void {
    $this->scanContrib = $scan_contrib;
  }

}
