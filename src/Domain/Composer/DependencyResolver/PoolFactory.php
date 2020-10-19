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
   * The original Composer package pool.
   *
   * @var \Composer\DependencyResolver\Pool
   */
  private $originalPool;

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
   * Constructs an instance.
   *
   * @param \Composer\DependencyResolver\Pool $pool
   *   The original Composer package pool.
   */
  public function __construct(Pool $pool) {
    $this->originalPool = $pool;
    $this->io = new NullIO();
  }

  /**
   * Creates a Composer package pool for Packagist only.
   *
   * @return \Composer\DependencyResolver\Pool
   *   The Composer package pool.
   */
  public function createWithPackagistOnly(): Pool {
    if ($this->packagistOnlyPool) {
      return $this->packagistOnlyPool;
    }

    $this->packagistOnlyPool = $this->originalPool;

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
  public function createWithDrupalDotOrg(): Pool {
    if ($this->drupalDotOrgPool) {
      return $this->drupalDotOrgPool;
    }

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
