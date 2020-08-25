<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Package\PackageManager;

/**
 * Provides a factory for fixture options.
 */
class FixtureOptionsFactory {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Composer\Composer
   */
  private $composer;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Package\PackageManager
   */
  private $packageManager;

  /**
   * The Drupal core version finder.
   *
   * @var \Acquia\Orca\Drupal\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionFinder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Composer\Composer $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Drupal\DrupalCoreVersionFinder $drupal_core_version_finder
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(Composer $composer, DrupalCoreVersionFinder $drupal_core_version_finder, PackageManager $package_manager) {
    $this->composer = $composer;
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    $this->packageManager = $package_manager;
  }

  /**
   * Creates a FixtureOptions instance.
   *
   * @param array $options
   *   An array of options data.
   *
   * @return \Acquia\Orca\Fixture\FixtureOptions
   *   A fixture options object.
   *
   * @throws \Acquia\Orca\Helper\Exception\OrcaInvalidArgumentException
   */
  public function create(array $options): FixtureOptions {
    return new FixtureOptions($this->composer, $this->drupalCoreVersionFinder, $this->packageManager, $options);
  }

}
