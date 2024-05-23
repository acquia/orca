<?php

namespace Acquia\Orca\Tests\Options;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Options\FixtureOptionsFactory;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 *
 * @coversDefaultClass \Acquia\Orca\Options\FixtureOptionsFactory
 */
class FixtureOptionsFactoryTest extends TestCase {

  protected DrupalCoreVersionResolver|ObjectProphecy $drupalCoreVersionFinder;
  protected PackageManager|ObjectProphecy $packageManager;

  protected function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionResolver::class);
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

  public static function providerFactory(): array {
    return [
      [['bare' => TRUE], TRUE, FALSE],
      [['dev' => TRUE], FALSE, TRUE],
    ];
  }

}
