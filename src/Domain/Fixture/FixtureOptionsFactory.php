<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Composer\Composer;
use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Options\FixtureOptions;

/**
 * Provides a factory for fixture options.
 */
class FixtureOptionsFactory {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Domain\Composer\Composer
   */
  private $composer;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The Drupal core version finder.
   *
   * @var \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionFinder;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Composer $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder $drupal_core_version_finder
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
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
   * @return \Acquia\Orca\Options\FixtureOptions
   *   A fixture options object.
   *
   * @throws \Acquia\Orca\Exception\InvalidArgumentException
   */
  public function create(array $options): FixtureOptions {
    return new FixtureOptions($this->drupalCoreVersionFinder, $this->packageManager, $options);
  }

}
