<?php

namespace Acquia\Orca\Options;

use Acquia\Orca\Domain\Package\PackageManager;

/**
 * Provides a factory for CI run options.
 */
class CiRunOptionsFactory {

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(PackageManager $package_manager) {
    $this->packageManager = $package_manager;
  }

  /**
   * Creates a FixtureOptions instance.
   *
   * @param array $options
   *   An array of options data.
   *
   * @return \Acquia\Orca\Options\FixtureOptions
   *   A fixture options object.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  public function create(array $options): CiRunOptions {
    return new CiRunOptions($this->packageManager, $options);
  }

}
