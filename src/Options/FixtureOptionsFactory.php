<?php

namespace Acquia\Orca\Options;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\PackageManager;

/**
 * Provides a factory for fixture options.
 */
class FixtureOptionsFactory {

  /**
   * The Drupal core version finder.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionFinder;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_finder
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_finder, PackageManager $package_manager) {
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
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
  public function create(array $options): FixtureOptions {
    return new FixtureOptions($this->drupalCoreVersionFinder, $this->packageManager, $options);
  }

}
