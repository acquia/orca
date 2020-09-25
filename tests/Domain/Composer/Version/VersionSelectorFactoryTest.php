<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Composer\DependencyResolver\Pool;
use Composer\Package\Version\VersionSelector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Composer\DependencyResolver\Pool|\Prophecy\Prophecy\ObjectProphecy $packagePool
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
 */
class VersionSelectorFactoryTest extends TestCase {

  protected function setUp(): void {
    $this->packagePool = $this->prophesize(Pool::class);
  }

  protected function createVersionSelectorFactory(): VersionSelectorFactory {
    $package_pool = $this->packagePool->reveal();
    return new VersionSelectorFactory($package_pool);
  }

  /**
   * @covers ::__construct
   * @covers ::create
   */
  public function testCreate(): void {
    $this->packagePool
      ->addRepository(Argument::any())
      ->shouldBeCalledTimes(2);
    $factory = $this->createVersionSelectorFactory();

    $selector = $factory->create();

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(VersionSelector::class, $selector, 'Created a version selector.');
  }

}
