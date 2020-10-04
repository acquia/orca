<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Exception;
use RuntimeException;

/**
 * Tests a SUT for preconditions of use.
 */
class SutPreconditionsTester {

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The composer.json config.
   *
   * @var \Noodlehaus\Config|null
   */
  private $composerJsonConfig;

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The SUT as a package.
   *
   * @var \Acquia\Orca\Domain\Package\Package|null
   */
  private $sut;

  /**
   * The SUT path.
   *
   * @var string|null
   */
  private $sutPath;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(ConfigLoader $config_loader, FixturePathHandler $fixture_path_handler, PackageManager $package_manager) {
    $this->configLoader = $config_loader;
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
   * @throws \RuntimeException
   */
  public function test(string $package_name): void {
    $this->sut = $this->packageManager->get($package_name);
    $this->sutPath = $this->fixture->getPath($this->sut->getRepositoryUrlRaw());
    try {
      $this->loadComposerJson();
      $this->validateComposerJsonName();
    }
    catch (OrcaException $e) {
      throw $e;
    }
    catch (Exception $e) {
      throw new RuntimeException('An unknown error occurred while testing the SUT for preconditions of fixture creation');
    }
  }

  /**
   * Loads the composer.json.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  private function loadComposerJson(): void {
    try {
      $path = "{$this->sutPath}/composer.json";
      $this->composerJsonConfig = $this->configLoader
        ->load($path);
    }
    catch (OrcaFileNotFoundException $e) {
      throw new OrcaFileNotFoundException("SUT is missing root composer.json at {$path}", NULL, $e);
    }
  }

  /**
   * Validates the composer.json package name.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function validateComposerJsonName(): void {
    $actual_name = $this->composerJsonConfig->get('name');
    $expected_name = $this->sut->getPackageName();
    if ($actual_name !== $expected_name) {
      throw new OrcaException(sprintf(
        "SUT composer.json's 'name' value %s does not match expected %s",
        var_export($actual_name, TRUE),
        var_export($expected_name, TRUE
      )));
    }
  }

}
