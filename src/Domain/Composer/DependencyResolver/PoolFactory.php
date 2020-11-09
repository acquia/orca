<?php

namespace Acquia\Orca\Domain\Composer\DependencyResolver;

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Repository\RepositoryFactory;

/**
 * Creates a Composer package pool.
 */
class PoolFactory {

  /**
   * A Composer package pool with a dev minimum stability.
   *
   * @var \Acquia\Orca\Domain\Composer\DependencyResolver\DevPool
   */
  private $devPool;

  /**
   * The base Composer package pool.
   *
   * @var \Composer\DependencyResolver\Pool
   */
  private $basePool;

  /**
   * TRUE to use the dev pool as the base or FALSE to use the release pool.
   *
   * @var bool
   */
  private $dev;

  /**
   * A Composer package pool with Packagist and Drupal.org.
   *
   * @var \Composer\DependencyResolver\Pool
   */
  private $drupalDotOrgPool;

  /**
   * The IOInterface.
   *
   * @var \Composer\IO\NullIO
   */
  private $io;

  /**
   * A Composer package pool with Packagist only.
   *
   * @var \Composer\DependencyResolver\Pool
   */
  private $packagistOnlyPool;

  /**
   * A Composer package pool with an alpha minimum stability.
   *
   * @var \Acquia\Orca\Domain\Composer\DependencyResolver\ReleasePool
   */
  private $releasePool;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\DependencyResolver\DevPool $dev_pool
   *   A Composer package pool with a dev minimum stability.
   * @param \Acquia\Orca\Domain\Composer\DependencyResolver\ReleasePool $release_pool
   *   A Composer package pool with an alpha minimum stability.
   */
  public function __construct(DevPool $dev_pool, ReleasePool $release_pool) {
    $this->devPool = $dev_pool;
    $this->releasePool = $release_pool;
    $this->io = new NullIO();
  }

  /**
   * Creates a Pool instance.
   *
   * @param bool $include_drupal_dot_org
   *   TRUE to include the Drupal.org package repository or FALSE not to.
   * @param bool $dev
   *   TRUE to allow dev version results or FALSE not to.
   *
   * @return \Composer\DependencyResolver\Pool
   *   The Composer package pool.
   */
  public function create(bool $include_drupal_dot_org, bool $dev): Pool {
    $this->dev = $dev;
    $this->setBasePool();
    if ($include_drupal_dot_org) {
      return $this->createWithDrupalDotOrg();
    }
    return $this->createWithPackagistOnly();
  }

  /**
   * Sets the pool that will be used as the base for adding repositories.
   */
  private function setBasePool(): void {
    $this->basePool = $this->releasePool;
    if ($this->dev) {
      $this->basePool = $this->devPool;
    }
  }

  /**
   * Creates a Composer package pool for Packagist only.
   *
   * @return \Composer\DependencyResolver\Pool
   *   The Composer package pool.
   */
  private function createWithPackagistOnly(): Pool {
    $this->packagistOnlyPool = $this->basePool;

    $packagist = RepositoryFactory::defaultRepos($this->io)['packagist.org'];

    $this->packagistOnlyPool->addRepository($packagist);

    return $this->packagistOnlyPool;
  }

  /**
   * Creates a Composer package pool for Packagist and Drupal.org.
   *
   * @return \Composer\DependencyResolver\Pool
   *   The Composer package pool.
   */
  private function createWithDrupalDotOrg(): Pool {
    $this->drupalDotOrgPool = $this->createWithPackagistOnly();

    $drupal_org =
      RepositoryFactory::createRepo($this->io, Factory::createConfig($this->io), [
        'type' => 'composer',
        'url' => 'https://packages.drupal.org/8',
      ]);

    $this->drupalDotOrgPool->addRepository($drupal_org);

    return $this->drupalDotOrgPool;
  }

}
