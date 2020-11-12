<?php

namespace Acquia\Orca\Tests\Domain\Composer\DependencyResolver;

use Acquia\Orca\Domain\Composer\DependencyResolver\DevPool;
use Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory;
use Acquia\Orca\Domain\Composer\DependencyResolver\ReleasePool;
use Composer\DependencyResolver\Pool;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Composer\DependencyResolver\DevPool|\Prophecy\Prophecy\ObjectProphecy $devPool
 * @property \Acquia\Orca\Domain\Composer\DependencyResolver\ReleasePool|\Prophecy\Prophecy\ObjectProphecy $releasePool
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory
 */
class PoolFactoryTest extends TestCase {

  protected function setUp(): void {
    $this->devPool = $this->prophesize(DevPool::class);
    $this->releasePool = $this->prophesize(ReleasePool::class);
  }

  protected function createPoolFactory(): PoolFactory {
    $dev_pool = $this->devPool->reveal();
    $release_pool = $this->releasePool->reveal();
    return new PoolFactory($dev_pool, $release_pool);
  }

  /**
   * @dataProvider providerCreate
   *
   * @covers ::__construct
   * @covers ::create
   * @covers ::createWithDrupalDotOrg
   * @covers ::createWithPackagistOnly
   * @covers ::setBasePool
   */
  public function testCreate($include_drupal_dot_org, $dev, $add_dev_pool_repos, $add_release_pool_repos): void {
    $this->devPool
      ->addRepository(Argument::any())
      ->shouldBeCalledTimes($add_dev_pool_repos * 2);
    $this->releasePool
      ->addRepository(Argument::any())
      ->shouldBeCalledTimes($add_release_pool_repos * 2);
    $factory = $this->createPoolFactory();

    $pool = $factory->create($include_drupal_dot_org, $dev);
    // Call again to ensure no value caching, which leads to the unintuitive
    // result that all pools called in a single process will be whatever the
    // one is, dev or release, regardless of flags sent afterward.
    $factory->create($include_drupal_dot_org, $dev);

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(Pool::class, $pool, 'Created a Composer package pool.');
  }

  public function providerCreate(): array {
    return [
      [
        'include_drupal_dot_org' => TRUE,
        'dev' => TRUE,
        'add_dev_pool_repos' => 2,
        'add_release_pool_repos' => 0,
      ],
      [
        'include_drupal_dot_org' => TRUE,
        'dev' => FALSE,
        'add_dev_pool_repos' => 0,
        'add_release_pool_repos' => 2,
      ],
      [
        'include_drupal_dot_org' => FALSE,
        'dev' => TRUE,
        'add_dev_pool_repos' => 1,
        'add_release_pool_repos' => 0,
      ],
      [
        'include_drupal_dot_org' => FALSE,
        'dev' => FALSE,
        'add_dev_pool_repos' => 0,
        'add_release_pool_repos' => 1,
      ],
    ];
  }

}
