<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Package;
use PHPUnit\Framework\TestCase;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @covers \Acquia\Orca\Fixture\Package
 */
class PackageTest extends TestCase {

  public function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
  }

  /**
   * @dataProvider providerPackage
   */
  public function testPackage($data, $package_name, $project_name, $repository_url, $type, $version, $dev_version, $package_string, $dev_package_string, $install_path) {
    $package = $this->createPackage($data);

    $this->assertInstanceOf(Package::class, $package, 'Instantiated class.');
    $this->assertEquals($package_name, $package->getPackageName(), 'Set/got package name.');
    $this->assertEquals($project_name, $package->getProjectName(), 'Set/got project name.');
    $this->assertEquals($repository_url, $package->getRepositoryUrl(), 'Set/got repository URL.');
    $this->assertEquals($type, $package->getType(), 'Set/got type.');
    $this->assertEquals($version, $package->getVersionRecommended(), 'Set/got recommended version.');
    $this->assertEquals($dev_version, $package->getVersionDev(), 'Set/got dev version.');
    $this->assertEquals($package_string, $package->getPackageStringRecommended(), 'Got recommended dependency string.');
    $this->assertEquals($dev_package_string, $package->getPackageStringDev(), 'Got dev dependency string.');
    $this->assertEquals($install_path, $package->getInstallPathRelative(), 'Got relative install path.');
  }

  public function providerPackage() {
    return [
      'Full specification' => [
        [
          'name' => 'drupal/submodule',
          'install_path' => 'custom/path/to/submodule',
          'type' => 'library',
          'url' => '/var/www/submodule',
          'version' => '~1.0',
          'version_dev' => '1.x-dev',
        ],
        'drupal/submodule',
        'submodule',
        '/var/www/submodule',
        'library',
        '~1.0',
        '1.x-dev',
        'drupal/submodule:~1.0',
        'drupal/submodule:1.x-dev',
        'custom/path/to/submodule',
      ],
      'Minimum specification/default values' => [
        [
          'name' => 'drupal/example_module',
          'version_dev' => '2.x-dev',
        ],
        'drupal/example_module',
        'example_module',
        '../example_module',
        'drupal-module',
        '*',
        '2.x-dev',
        'drupal/example_module:*',
        'drupal/example_module:2.x-dev',
        'docroot/modules/contrib/example_module',
      ],
    ];
  }

  /**
   * @dataProvider providerConstructionError
   */
  public function testConstructionError($data, $message) {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);

    $this->createPackage($data);
  }

  public function providerConstructionError() {
    return [
      [['version_dev' => '1.x'], 'Missing required property: "name"'],
      [['name' => 'drupal/example'], 'Missing required property: "version_dev"'],
      [['name' => NULL, 'version_dev' => '1.x'], 'Invalid value for "name" property: NULL'],
      [['name' => '', 'version_dev' => '1.x'], 'Invalid value for "name" property: \'\''],
      [['name' => [], 'version_dev' => '1.x'], "Invalid value for \"name\" property: array (\n)"],
      [['name' => 'incomplete', 'version_dev' => '1.x'], 'Invalid value for "name" property: \'incomplete\''],
    ];
  }

  /**
   * @dataProvider providerInstallPathCalculation
   */
  public function testInstallPathCalculation($type, $relative_install_path) {
    $absolute_install_path = "/var/www/{$relative_install_path}";
    $this->fixture
      ->getPath($relative_install_path)
      ->willReturn($absolute_install_path);
    $data = [
      'name' => 'drupal/example',
      'type' => $type,
      'version_dev' => '1.x-dev',
    ];

    $package = $this->createPackage($data);

    $this->assertEquals($relative_install_path, $package->getInstallPathRelative());
    $this->assertEquals($absolute_install_path, $package->getInstallPathAbsolute());
  }

  public function providerInstallPathCalculation() {
    return [
      ['bower-asset', 'docroot/libraries/example'],
      ['drupal-core', 'docroot/core'],
      ['drupal-drush', 'drush/Commands/example'],
      ['drupal-library', 'docroot/libraries/example'],
      ['drupal-module', 'docroot/modules/contrib/example'],
      ['drupal-profile', 'docroot/profiles/contrib/example'],
      ['drupal-theme', 'docroot/themes/contrib/example'],
      ['npm-asset', 'docroot/libraries/example'],
      ['something-nonstandard', 'vendor/drupal/example'],
    ];
  }

  protected function createPackage($data): Package {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new Package($fixture, $data);
  }

}
