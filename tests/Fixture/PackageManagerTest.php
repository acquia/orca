<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Package;
use Acquia\Orca\Fixture\PackageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;

/**
 * @covers \Acquia\Orca\Fixture\PackageManager
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Yaml\Parser $parser
 */
class PackageManagerTest extends TestCase {

  private const PACKAGES_DATA = [
    ['name' => 'drupal/module1'],
    ['name' => 'drupal/module2', 'version' => '~1.0'],
    ['name' => 'drupal/drush1', 'type' => 'drupal-drush'],
    ['name' => 'drupal/drush2', 'type' => 'drupal-drush'],
    ['name' => 'drupal/theme1', 'type' => 'drupal-theme'],
    ['name' => 'drupal/theme2', 'type' => 'drupal-theme'],
  ];

  private const ALL_PACKAGES = [
    'drupal/module1',
    'drupal/module2',
    'drupal/drush1',
    'drupal/drush2',
    'drupal/theme1',
    'drupal/theme2',
  ];

  private const ALL_PACKAGE_VERSIONS = [
    'drupal/module1' => '*',
    'drupal/module2' => '~1.0',
    'drupal/drush1' => '*',
    'drupal/drush2' => '*',
    'drupal/theme1' => '*',
    'drupal/theme2' => '*',
  ];

  private const MODULES = [
    'drupal/module1',
    'drupal/module2',
  ];

  private const MODULE_DIR_BASENAMES = [
    'drupal/module1' => '../module1',
    'drupal/module2' => '../module2',
  ];

  private const THEMES = [
    'drupal/theme1',
    'drupal/theme2',
  ];

  private $projectDir = '/var/www/orca';

  private $packagesConfig = 'config/packages.yml';

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->parser = $this->prophesize(Parser::class);
    $this->parser
      ->parseFile('/var/www/orca/config/packages.yml')
      ->shouldBeCalledTimes(1)
      ->willReturn(self::PACKAGES_DATA);
  }

  public function testPackageManager() {
    $manager = $this->createPackageManager();
    $all_packages = $manager->getMultiple();
    $all_package_versions = $manager->getMultiple(NULL, 'getVersion');
    $modules = $manager->getMultiple('drupal-module');
    $module_repository_urls = $manager->getMultiple('drupal-module', 'getRepositoryUrl');
    $themes = $manager->getMultiple('drupal-theme');
    $package = $manager->get('drupal/module2');

    $this->assertEquals(self::ALL_PACKAGES, array_keys($all_packages), 'Set/got all packages.');
    $this->assertInstanceOf(Package::class, reset($all_packages));
    $this->assertEquals(self::ALL_PACKAGE_VERSIONS, $all_package_versions);
    $this->assertEquals(self::MODULES, array_keys($modules), 'Got modules.');
    $this->assertInstanceOf(Package::class, reset($modules));
    $this->assertEquals(self::MODULE_DIR_BASENAMES, $module_repository_urls, 'Got module repository URLs.');
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

  /**
   * @return \Acquia\Orca\Fixture\PackageManager
   */
  private function createPackageManager(): PackageManager {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Symfony\Component\Yaml\Parser $parser */
    $parser = $this->parser->reveal();
    $object = new PackageManager($fixture, $parser, $this->packagesConfig, $this->projectDir);
    $this->assertInstanceOf(PackageManager::class, $object, 'Instantiated class.');
    return $object;
  }

}
