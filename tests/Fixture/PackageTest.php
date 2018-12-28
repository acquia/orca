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
  public function testPackage($data, $package_name, $project_name, $repository_url, $type, $version, $package_string, $install_path) {
    $package = $this->createPackage($data);

    $this->assertInstanceOf(Package::class, $package, 'Instantiated class.');
    $this->assertEquals($package_name, $package->getPackageName(), 'Set/got package name.');
    $this->assertEquals($project_name, $package->getProjectName(), 'Set/got project name.');
    $this->assertEquals($repository_url, $package->getRepositoryUrl(), 'Set/got repository URL.');
    $this->assertEquals($type, $package->getType(), 'Set/got type.');
    $this->assertEquals($version, $package->getVersion(), 'Set/got version.');
    $this->assertEquals($package_string, $package->getPackageString(), 'Got dependency string.');
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
        ],
        'drupal/submodule',
        'submodule',
        '/var/www/submodule',
        'library',
        '~1.0',
        'drupal/submodule:~1.0',
        'custom/path/to/submodule',
      ],
      'Minimum specification/default values' => [
        [
          'name' => 'drupal/example_module',
        ],
        'drupal/example_module',
        'example_module',
        '../example_module',
        'drupal-module',
        '*',
        'drupal/example_module:*',
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
      [[], 'Missing required property: "name"'],
      [['name' => NULL], 'Invalid value for "name" property: NULL'],
      [['name' => ''], 'Invalid value for "name" property: \'\''],
      [['name' => []], "Invalid value for \"name\" property: array (\n)"],
      [['name' => 'incomplete'], 'Invalid value for "name" property: \'incomplete\''],
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
    ];

    $package = $this->createPackage($data);

    $this->assertEquals($relative_install_path, $package->getInstallPathRelative());
    $this->assertEquals($absolute_install_path, $package->getInstallPathAbsolute());
  }

  public function providerInstallPathCalculation() {
    return [
      ['drupal-module', 'docroot/modules/contrib/example'],
      ['drupal-drush', 'drush/Commands/example'],
      ['drupal-theme', 'docroot/themes/contrib/example'],
      ['drupal-profile', 'docroot/profiles/contrib/example'],
      ['drupal-library', 'docroot/libraries/example'],
      ['bower-asset', 'docroot/libraries/example'],
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
