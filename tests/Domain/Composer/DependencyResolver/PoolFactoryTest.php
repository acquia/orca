<?php

namespace Acquia\Orca\Tests\Domain\Composer\DependencyResolver;

use Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory;
use Composer\DependencyResolver\Pool;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Composer\DependencyResolver\Pool|\Prophecy\Prophecy\ObjectProphecy $pool
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory
 */
class PoolFactoryTest extends TestCase {

  protected function setUp(): void {
    $this->pool = $this->prophesize(Pool::class);
  }

  protected function createPoolFactory(): PoolFactory {
    $pool = $this->pool->reveal();
    return new PoolFactory($pool);
  }

  /**
   * @covers ::__construct
   * @covers ::createWithPackagistOnly
   */
  public function testCreatePackagistOnly(): void {
    $this->pool
      ->addRepository(Argument::any())
      ->shouldBeCalledTimes(2);
    $factory = $this->createPoolFactory();

    $pool = $factory->createWithPackagistOnly();
    // Call again to test value caching.
    $factory->createWithDrupalDotOrg();

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(Pool::class, $pool, 'Created a Composer package pool with Packagist only.');
  }

  /**
   * @covers ::__construct
   * @covers ::createWithDrupalDotOrg
   * @covers ::createWithPackagistOnly
   */
  public function testCreateWithDrupalDotOrg(): void {
    $this->pool
      ->addRepository(Argument::any())
      ->shouldBeCalledTimes(2);
    $factory = $this->createPoolFactory();

    $pool = $factory->createWithDrupalDotOrg();
    // Call again to test value caching.
    $factory->createWithDrupalDotOrg();

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(Pool::class, $pool, 'Created a Composer package pool with Packagist and Drupal.org.');
  }

}
