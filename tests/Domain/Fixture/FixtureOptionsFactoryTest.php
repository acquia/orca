<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Composer\Composer;
use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Domain\Fixture\FixtureOptionsFactory;
use Acquia\Orca\Domain\Package\PackageManager;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Domain\Composer\Composer|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 *
 * @coversDefaultClass \Acquia\Orca\Domain\Fixture\FixtureOptionsFactory
 */
class FixtureOptionsFactoryTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(Composer::class);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  private function createFixtureOptionsFactory(): FixtureOptionsFactory {
    $composer = $this->composer->reveal();
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new FixtureOptionsFactory($composer, $drupal_core_version_finder, $package_manager);
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
