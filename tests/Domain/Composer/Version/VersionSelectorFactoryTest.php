<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory;
use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Composer\Package\Version\VersionSelector;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
 */
class VersionSelectorFactoryTest extends TestCase {

  /**
   * The Composer pool factory.
   *
   * @var \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  private $poolFactory;

  protected function setUp(): void {
    $this->poolFactory = $this->prophesize(PoolFactory::class);
  }

  protected function createVersionSelectorFactory(): VersionSelectorFactory {
    $pool_factory = $this->poolFactory->reveal();
    return new VersionSelectorFactory($pool_factory);
  }

  /**
   * @covers ::__construct
   * @covers ::create
   */
  public function testCreate(): void {
    $this->poolFactory
      ->createWithDrupalDotOrg()
      ->shouldBeCalledOnce();
    $factory = $this->createVersionSelectorFactory();

    $selector = $factory->create();

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(VersionSelector::class, $selector, 'Created a version selector.');
  }

}
