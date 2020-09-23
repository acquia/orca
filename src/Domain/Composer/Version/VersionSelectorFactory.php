<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\RepositoryFactory;

/**
 * Creates a Composer version selector.
 */
class VersionSelectorFactory {

  /**
   * The Composer package pool.
   *
   * @var \Composer\DependencyResolver\Pool
   */
  private $packagePool;

  /**
   * Constructs an instance.
   *
   * @param \Composer\DependencyResolver\Pool $package_pool
   *   The Composer package pool.
   */
  public function __construct(Pool $package_pool) {
    $this->packagePool = $package_pool;
  }

  /**
   * Creates a Composer version selector for Packagist and Drupal.org.
   *
   * @return \Composer\Package\Version\VersionSelector
   *   The version selector.
   */
  public function create(): VersionSelector {
    $io = new NullIO();
    $packagist = RepositoryFactory::defaultRepos($io)['packagist.org'];
    $drupal_org = RepositoryFactory::createRepo($io, Factory::createConfig($io), [
      'type' => 'composer',
      'url' => 'https://packages.drupal.org/8',
    ]);

    $this->packagePool->addRepository($packagist);
    $this->packagePool->addRepository($drupal_org);

    return new VersionSelector($this->packagePool);
  }

}
