<?php

namespace Acquia\Orca\Tests\Options;

use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Options\FixtureOptionsFactory;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 *
 * @coversDefaultClass \Acquia\Orca\Options\FixtureOptionsFactory
 */
class FixtureOptionsFactoryTest extends TestCase {

  protected function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  private function createFixtureOptionsFactory(): FixtureOptionsFactory {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new FixtureOptionsFactory($drupal_core_version_finder, $package_manager);
  }

  /**
   * @dataProvider providerFactory
   */
  public function testFactory($options, $is_bare, $is_dev): void {
    $factory = $this->createFixtureOptionsFactory();

    $options = $factory->create($options);

    self::assertEquals($is_bare, $options->isBare());
    self::assertEquals($is_dev, $options->isDev());
  }

  public function providerFactory(): array {
    return [
      [['bare' => TRUE], TRUE, FALSE],
      [['dev' => TRUE], FALSE, TRUE],
    ];
  }

}
