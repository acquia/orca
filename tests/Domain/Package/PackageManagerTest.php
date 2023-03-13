<?php

namespace Acquia\Orca\Tests\Domain\Package;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;

/**
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Yaml\Parser $parser
 * @coversDefaultClass \Acquia\Orca\Domain\Package\PackageManager
 */
class PackageManagerTest extends TestCase {

  private const PACKAGES_DATA = [
    'drupal/module1' => [],
    'drupal/module2' => ['version_dev' => '1.x-dev'],
    'drupal/module3' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
    'drupal/module4' => [
      'version' => '~1.0',
      'version_dev' => '1.x-dev',
      'core_matrix' => [
        '*' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
      ],
    ],
    'drupal/module5' => [
      'version' => NULL,
      'version_dev' => NULL,
      'core_matrix' => [
        '*' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
      ],
    ],
    'drupal/module6' => ['type' => 'composer-plugin'],
    'drupal/drush1' => ['type' => 'drupal-drush', 'version_dev' => '1.x-dev'],
    'drupal/drush2' => ['type' => 'drupal-drush', 'version_dev' => '1.x-dev'],
    'drupal/theme1' => ['type' => 'drupal-theme', 'version_dev' => '1.x-dev'],
    'drupal/theme2' => ['type' => 'drupal-theme', 'version_dev' => '1.x-dev'],
    'drupal/remove_me1' => [],
    'drupal/remove_me2' => ['version_dev' => '1.x-dev'],
    'drupal/remove_me3' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
    'drupal/remove_me4' => [
      'core_matrix' => [
        '*' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
      ],
    ],
    'drupal/remove_me5' => [
      'core_matrix' => [
        '*' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
      ],
    ],
  ];

  private const PACKAGES_DATA_ALTER = [
    'drupal/add_me1' => [],
    'drupal/add_me2' => ['version' => '~1.0'],
    'drupal/add_me3' => ['version' => '~1.0', 'version_dev' => '1.x-dev'],
    'drupal/add_me4' => ['type' => 'library'],
    'drupal/remove_me1' => NULL,
    'drupal/remove_me2' => ['version' => NULL, 'version_dev' => NULL],
    'drupal/remove_me3' => ['version' => NULL, 'version_dev' => NULL],
    'drupal/remove_me4' => [
      'core_matrix' => [
        '*' => ['version' => NULL, 'version_dev' => NULL],
      ],
    ],
    'drupal/remove_me5' => [
      'core_matrix' => NULL,
    ],
    'drupal/no_match' => NULL,
  ];

  private const EXPECTED_PACKAGE_LIST = [
    'drupal/add_me1' => 0,
    'drupal/add_me2' => 0,
    'drupal/add_me3' => 0,
    'drupal/add_me4' => 0,
    'drupal/drush1' => 0,
    'drupal/drush2' => 0,
    'drupal/module1' => 0,
    'drupal/module2' => 0,
    'drupal/module3' => 0,
    'drupal/module4' => 0,
    'drupal/module5' => 0,
    'drupal/module6' => 0,
    'drupal/theme1' => 0,
    'drupal/theme2' => 0,
  ];

  private const ORCA_PATH = '/var/www/orca';

  private const PACKAGES_CONFIG_FILE = 'config/packages.yml';

  private const PACKAGES_CONFIG_ALTER_FILE = '../example/packages.yml';

  protected function setUp(): void {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->exists(Argument::any())
      ->willReturn(TRUE);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->orca
      ->getPath(self::PACKAGES_CONFIG_FILE)
      ->willReturn(self::ORCA_PATH . '/' . self::PACKAGES_CONFIG_FILE);
    $this->orca
      ->getPath()
      ->willReturn(self::ORCA_PATH);
    $this->parser = $this->prophesize(Parser::class);
    $this->parser
      ->parseFile('/var/www/orca/config/packages.yml')
      ->willReturn(self::PACKAGES_DATA);
    $this->parser
      ->parseFile(self::PACKAGES_CONFIG_ALTER_FILE)
      ->willReturn(self::PACKAGES_DATA_ALTER);
  }

  private function createPackageManager(): PackageManager {
    $filesystem = $this->filesystem->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $parser = $this->parser->reveal();
    return new PackageManager($filesystem, $fixture_path_handler, $orca_path_handler, $parser, self::PACKAGES_CONFIG_FILE, self::PACKAGES_CONFIG_ALTER_FILE);
  }

  public function testConstructionAndGetters(): void {
    $manager = $this->createPackageManager();
    $all_packages = $manager->getAll();
    $package = $manager->get('drupal/module2');

    // Normalize expected package list for clearer comparison.
    $actual_package_list = [];
    foreach (array_keys($all_packages) as $name) {
      $actual_package_list[$name] = 0;
    }

    self::assertEquals(self::EXPECTED_PACKAGE_LIST, $actual_package_list, 'Set/got all packages.');
    self::assertInstanceOf(Package::class, reset($all_packages), 'Got packages as Package objects.');
    self::assertEquals('drupal/module2', $package->getPackageName(), 'Got package by name.');
  }

  public function testRequestingNonExistentPackage(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('No such package: nonexistent/package');

    $manager = $this->createPackageManager();
    $manager->get('nonexistent/package');
  }

  /**
   * @dataProvider providerCheckingPackageExistence
   */
  public function testCheckingPackageExistence($package_name, $expected): void {
    $manager = $this->createPackageManager();
    $actual = $manager->exists($package_name);

    self::assertEquals($expected, $actual, 'Correctly tested for package existence.');
  }

  public function providerCheckingPackageExistence(): array {
    return [
      ['drupal/module1', TRUE],
      ['nonexistent/package', FALSE],
    ];
  }

  public function testParseMissingYamlFile(): void {
    $this->orca
      ->getPath(self::PACKAGES_CONFIG_FILE)
      ->willReturnArgument();
    $this->filesystem
      ->exists(self::PACKAGES_CONFIG_FILE)
      ->willReturn(FALSE);
    $this->expectException(\LogicException::class);

    $manager = $this->createPackageManager();
    $manager->getAlterData();
  }

  public function testParseInvalidYamlFile(): void {
    $this->orca
      ->getPath(self::PACKAGES_CONFIG_FILE)
      ->willReturnArgument();
    $this->parser
      ->parseFile(self::PACKAGES_CONFIG_FILE)
      ->willReturn(NULL);
    $this->expectException(\LogicException::class);

    $manager = $this->createPackageManager();
    $manager->getAlterData();
  }

}
