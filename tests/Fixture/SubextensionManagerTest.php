<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\SubextensionManager;
use Acquia\Orca\Utility\ConfigLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ConfigLoader $configLoader
 */
class SubextensionManagerTest extends TestCase {

  protected function setUp() {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  public function testConstruction() {
    /** @var \Acquia\Orca\Utility\ConfigLoader $config_loader */
    $config_loader = $this->configLoader->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $this->packageManager
      ->getAlterData()
      ->willReturn([]);
    $this->packageManager
      ->getMultiple('drupal-module')
      ->willReturn([]);
    $this->packageManager
      ->getMultiple('drupal-theme')
      ->willReturn([]);
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    $object = new SubextensionManager($config_loader, $filesystem, $fixture, $package_manager);

    $this->assertInstanceOf(SubextensionManager::class, $object, 'Instantiated class.');
  }

}
