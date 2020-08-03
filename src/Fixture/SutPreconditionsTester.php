<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Package\PackageManager;
use Composer\Json\JsonFile;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests a SUT for preconditions of use.
 */
class SutPreconditionsTester {

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler, PackageManager $package_manager) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->packageManager = $package_manager;
  }

  /**
   * Tests the preconditions for using a given system under test (SUT).
   *
   * @param string $package_name
   *   The package name of the SUT, e.g., "drupal/example".
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  public function test(string $package_name): void {
    $sut = $this->packageManager->get($package_name);
    $path = $this->fixture->getPath($sut->getRepositoryUrlRaw());

    if (!$this->filesystem->exists($path)) {
      throw new OrcaException(sprintf('SUT is absent from expected location: %s', $path));
    }

    $composer_json = new JsonFile("{$path}/composer.json");
    if (!$composer_json->exists()) {
      throw new OrcaException(sprintf('SUT is missing root composer.json'));
    }

    $data = $composer_json->read();

    $actual_name = isset($data['name']) ? $data['name'] : NULL;
    $expected_name = $sut->getPackageName();
    if ($actual_name !== $expected_name) {
      throw new OrcaException(sprintf(
        "SUT composer.json's 'name' value %s does not match expected %s",
        var_export($actual_name, TRUE),
        var_export($expected_name, TRUE
      )));
    }
  }

}
