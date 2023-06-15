<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Fixture\SubextensionManager;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Config\ConfigLoader $configLoader
 */
class SubextensionManagerTest extends TestCase {

  protected ObjectProphecy|FixturePathHandler $fixture;
  protected OrcaPathHandler|ObjectProphecy $orca;
  protected ObjectProphecy|PackageManager $packageManager;
  protected ObjectProphecy|Filesystem $filesystem;
  protected ObjectProphecy|ConfigLoader $configLoader;

  protected function setUp(): void {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  public function testConstruction(): void {
    $config_loader = $this->configLoader->reveal();
    $filesystem = $this->filesystem->reveal();
    $fixture = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $this->packageManager
      ->getAlterData()
      ->willReturn([]);
    $this->packageManager
      ->getCompanyPackages()
      ->willReturn([]);
    $package_manager = $this->packageManager->reveal();
    $object = new SubextensionManager($config_loader, $filesystem, $fixture, $orca_path_handler, $package_manager);

    self::assertInstanceOf(SubextensionManager::class, $object, 'Instantiated class.');
  }

}
