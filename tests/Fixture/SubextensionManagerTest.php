<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\SubextensionManager;
use Acquia\Orca\Utility\ConfigLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Acquia\Orca\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ConfigLoader $configLoader
 */
class SubextensionManagerTest extends TestCase {

  protected function setUp() {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  public function testConstruction() {
    $config_loader = $this->configLoader->reveal();
    $filesystem = $this->filesystem->reveal();
    $fixture = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $this->packageManager
      ->getAlterData()
      ->willReturn([]);
    $this->packageManager
      ->getAll()
      ->willReturn([]);
    $package_manager = $this->packageManager->reveal();
    $object = new SubextensionManager($config_loader, $filesystem, $fixture, $orca_path_handler, $package_manager);

    $this->assertInstanceOf(SubextensionManager::class, $object, 'Instantiated class.');
  }

}
