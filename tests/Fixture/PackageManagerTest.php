<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Package\Package;
use Acquia\Orca\Package\PackageManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;

/**
 * @covers \Acquia\Orca\Package\PackageManager
 *
 * @property \Acquia\Orca\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Yaml\Parser $parser
 */
class PackageManagerTest extends TestCase {

  private const PACKAGES_DATA = [
    'drupal/module1' => ['version_dev' => '1.x-dev'],
    'drupal/module2' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
    'drupal/drush1' => ['type' => 'drupal-drush', 'version_dev' => '1.x-dev'],
    'drupal/drush2' => ['type' => 'drupal-drush', 'version_dev' => '1.x-dev'],
    'drupal/theme1' => ['type' => 'drupal-theme', 'version_dev' => '1.x-dev'],
    'drupal/theme2' => ['type' => 'drupal-theme', 'version_dev' => '1.x-dev'],
    'drupal/remove_me' => ['version_dev' => '1.x-dev'],
  ];

  private const PACKAGES_DATA_ALTER = [
    'drupal/remove_me' => NULL,
  ];

  private const ALL_PACKAGES = [
    'drupal/drush1',
    'drupal/drush2',
    'drupal/module1',
    'drupal/module2',
    'drupal/theme1',
    'drupal/theme2',
  ];

  private const ORCA_PATH = '/var/www/orca';

  private const PACKAGES_CONFIG_FILE = 'config/packages.yml';

  private const PACKAGES_CONFIG_ALTER_FILE = '../example/packages.yml';

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->exists(Argument::any())
      ->willReturn(TRUE);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(self::PACKAGES_CONFIG_FILE)
      ->willReturn(self::ORCA_PATH . '/' . self::PACKAGES_CONFIG_FILE);
    $this->orca
      ->getPath(self::PACKAGES_CONFIG_ALTER_FILE)
      ->willReturn(self::ORCA_PATH . '/' . self::PACKAGES_CONFIG_ALTER_FILE);
    $this->orca
      ->getPath()
      ->willReturn(self::ORCA_PATH);
    $this->parser = $this->prophesize(Parser::class);
    $this->parser
      ->parseFile('/var/www/orca/config/packages.yml')
      ->shouldBeCalledTimes(1)
      ->willReturn(self::PACKAGES_DATA);
    $this->parser
      ->parseFile('/var/www/orca/../example/packages.yml')
      ->shouldBeCalledTimes(1)
      ->willReturn(self::PACKAGES_DATA_ALTER);
  }

  public function testPackageManager() {
    $manager = $this->createPackageManager();
    $all_packages = $manager->getAll();
    $package = $manager->get('drupal/module2');

    $this->assertEquals(self::ALL_PACKAGES, array_keys($all_packages), 'Set/got all packages.');
    $this->assertInstanceOf(Package::class, reset($all_packages));
    $this->assertEquals('drupal/module2', $package->getPackageName(), 'Got package by name.');
  }

  public function testRequestingNonExistentPackage() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('No such package: nonexistent/package');

    $manager = $this->createPackageManager();
    $manager->get('nonexistent/package');
  }

  /**
   * @dataProvider providerCheckingPackageExistence
   */
  public function testCheckingPackageExistence($package_name, $expected) {
    $manager = $this->createPackageManager();
    $actual = $manager->exists($package_name);

    $this->assertEquals($expected, $actual, 'Correctly tested for package existence.');
  }

  public function providerCheckingPackageExistence() {
    return [
      ['drupal/module1', TRUE],
      ['nonexistent/package', FALSE],
    ];
  }

  private function createPackageManager(): PackageManager {
    $filesystem = $this->filesystem->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $parser = $this->parser->reveal();
    $object = new PackageManager($filesystem, $fixture_path_handler, $orca_path_handler, $parser, self::PACKAGES_CONFIG_FILE, self::PACKAGES_CONFIG_ALTER_FILE);
    $this->assertInstanceOf(PackageManager::class, $object, 'Instantiated class.');
    return $object;
  }

}
