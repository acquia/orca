<?php

namespace Acquia\Orca\Options;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\PackageManager;

/**
 * Provides a factory for fixture options.
 */
class FixtureOptionsFactory {

  /**
   * The Drupal core version resolver.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionResolver;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_resolver, PackageManager $package_manager) {
    $this->drupalCoreVersionResolver = $drupal_core_version_resolver;
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
    return new FixtureOptions($this->drupalCoreVersionResolver, $this->packageManager, $options);
  }

}
