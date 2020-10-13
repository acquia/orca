<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory;
use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Composer\DependencyResolver\Pool;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory|\Prophecy\Prophecy\ObjectProphecy $poolFactory
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
 */
class VersionSelectorFactoryTest extends TestCase {

  protected function setUp(): void {
    $this->poolFactory = $this->prophesize(PoolFactory::class);
  }

  protected function createVersionSelectorFactory(): VersionSelectorFactory {
    $pool_factory = $this->poolFactory->reveal();
    return new VersionSelectorFactory($pool_factory);
  }

  /**
   * @dataProvider providerCreate
   * @covers ::create
   */
  public function testCreate($include_drupal_dot_org, $dev): void {
    $pool = $this->prophesize(Pool::class)->reveal();
    $this->poolFactory
      ->create($include_drupal_dot_org, $dev)
      ->shouldBeCalledOnce()
      ->willReturn($pool);

    $selector_factory = $this->createVersionSelectorFactory();

    $selector_factory->create($include_drupal_dot_org, $dev);
  }

  public function providerCreate(): array {
    return [
      [
        'include_drupal_dot_org' => TRUE,
        'dev' => TRUE,
      ],
      [
        'include_drupal_dot_org' => TRUE,
        'dev' => FALSE,
      ],
      [
        'include_drupal_dot_org' => FALSE,
        'dev' => TRUE,
      ],
      [
        'include_drupal_dot_org' => FALSE,
        'dev' => FALSE,
      ],
    ];
  }

}
