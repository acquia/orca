<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Package;
use Acquia\Orca\Fixture\PackageManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;

/**
 * @covers \Acquia\Orca\Fixture\PackageManager
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
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

  private const MODULES = [
    'drupal/module1',
    'drupal/module2',
  ];

  private const THEMES = [
    'drupal/theme1',
    'drupal/theme2',
  ];

  private $projectDir = '/var/www/orca';

  private $packagesConfig = 'config/packages.yml';

  private $packagesConfigAlter = '../example/packages.yml';

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->exists(Argument::any())
      ->willReturn(TRUE);
    $this->fixture = $this->prophesize(Fixture::class);
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
    $all_packages = $manager->getMultiple();
    $modules = $manager->getMultiple('drupal-module');
    $themes = $manager->getMultiple('drupal-theme');
    $package = $manager->get('drupal/module2');

    $this->assertEquals(self::ALL_PACKAGES, array_keys($all_packages), 'Set/got all packages.');
    $this->assertInstanceOf(Package::class, reset($all_packages));
    $this->assertEquals(self::MODULES, array_keys($modules), 'Got modules.');
    $this->assertInstanceOf(Package::class, reset($modules));
    $this->assertEquals(self::THEMES, array_keys($themes), 'Got themes.');
    $this->assertInstanceOf(Package::class, reset($themes));
    $this->assertEquals('drupal/module2', $package->getPackageName(), 'Got package by name.');
    $this->assertInstanceOf(Package::class, $package);
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
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Symfony\Component\Yaml\Parser $parser */
    $parser = $this->parser->reveal();
    $object = new PackageManager($filesystem, $fixture, $parser, $this->packagesConfig, $this->packagesConfigAlter, $this->projectDir);
    $this->assertInstanceOf(PackageManager::class, $object, 'Instantiated class.');
    return $object;
  }

}
