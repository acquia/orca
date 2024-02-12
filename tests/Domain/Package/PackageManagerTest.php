<?php

namespace Acquia\Orca\Tests\Domain\Package;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
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
    'drupal/package' => ['type' => 'composer-plugin'],
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
    'drupal/dependency1' => [
      'is_company_package' => FALSE,
      'version' => '*',
      'version_dev' => '*',
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
    'drupal/no_match' => NULL,
    'drupal/add_dependency2' => [
      'is_company_package' => FALSE,
      'core_matrix' => [
        '9.x' => ['version' => '11.x', 'version_dev' => '11.x'],
        '*' => ['version' => '12.x', 'version_dev' => '12.x-dev'],
      ],
    ],
    'drupal/example_sut' => [],
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
    'drupal/package' => 0,
    'drupal/theme1' => 0,
    'drupal/theme2' => 0,
    'drupal/example_sut' => 0,
  ];

  private const EXPECTED_DEPENDENCY_LIST = [
    'drupal/dependency1' => 0,
    'drupal/add_dependency2' => 0,
  ];

  private const ORCA_PATH = '/var/www/orca';

  private const ORCA_SUT_DIR = '/var/www/example-123';

  private const ORCA_SUT_NAME = 'drupal/example_sut';

  private const PACKAGES_CONFIG_FILE = 'config/packages.yml';

  private const PACKAGES_CONFIG_ALTER_FILE = '../example/packages.yml';

  protected OrcaPathHandler|ObjectProphecy $orca;

  protected ObjectProphecy|Filesystem $filesystem;

  protected ObjectProphecy|FixturePathHandler $fixture;

  protected ObjectProphecy|Parser $parser;

  protected ObjectProphecy|EnvFacade $env;

  protected function setUp(): void {
    $this->env = $this->prophesize(EnvFacade::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->isAbsolutePath(Argument::any())
      ->willReturn(TRUE);
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
    $env = $this->env->reveal();
    $filesystem = $this->filesystem->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $parser = $this->parser->reveal();
    return new PackageManager($env, $filesystem, $fixture_path_handler, $orca_path_handler, $parser, self::PACKAGES_CONFIG_FILE, self::PACKAGES_CONFIG_ALTER_FILE);
  }

  public function testConstructionAndGetters(): void {
    $manager = $this->createPackageManager();
    $all_packages = $manager->getCompanyPackages();
    $all_dependencies = $manager->getThirdPartyDependencies();
    $package = $manager->get('drupal/module2');

    // Normalize expected package list for clearer comparison.
    $actual_package_list = [];
    foreach (array_keys($all_packages) as $name) {
      $actual_package_list[$name] = 0;
    }

    // Normalize expected dependency list for clearer comparison.
    $actual_dependency_list = [];
    foreach (array_keys($all_dependencies) as $name) {
      $actual_dependency_list[$name] = 0;
    }

    self::assertEquals(self::EXPECTED_PACKAGE_LIST, $actual_package_list, 'Set/got all packages.');
    self::assertInstanceOf(Package::class, reset($all_packages), 'Got packages as Package objects.');
    self::assertEquals('drupal/module2', $package->getPackageName(), 'Got package by name.');
    self::assertEquals(self::EXPECTED_DEPENDENCY_LIST, $actual_dependency_list, 'Set/got all dependencies.');
    self::assertEquals(TRUE, $package->isCompanyPackage(), 'Got a company package.');
    self::assertEquals(FALSE, $manager->get('drupal/dependency1')
      ->isCompanyPackage(), 'Got a third party dependency.');
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

  public function testSetSutUrl(): void {
    $this->env
      ->get('ORCA_SUT_NAME')
      ->willReturn(self::ORCA_SUT_NAME);
    $this->env
      ->get('ORCA_SUT_DIR')
      ->willReturn(self::ORCA_SUT_DIR);
    $manager = $this->createPackageManager();
    $example_sut = $manager->get('drupal/example_sut');
    $non_sut = $manager->get('drupal/module2');

    $package_name_parts = explode('/', self::ORCA_SUT_DIR);
    $expected_sut_url = "../" . end($package_name_parts);

    $sut_url = $example_sut->getRepositoryUrlRaw();
    $non_sut_url = $non_sut->getRepositoryUrlRaw();

    self::assertEquals($expected_sut_url, $sut_url, 'Url correctly set.');
    self::assertNotEquals($non_sut_url, $sut_url, "Non sut packages don't contain sut url.");
  }

}
