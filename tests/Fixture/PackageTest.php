<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

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
  public function testPackage($data, $package_name, $project_name, $type, $repository_url, $version, $dev_version, $enable, $package_string, $dev_package_string, $install_path) {
    $package = $this->createPackage($package_name, $data);

    $this->assertInstanceOf(Package::class, $package, 'Instantiated class.');
    $this->assertEquals($package_name, $package->getPackageName(), 'Set/got package name.');
    $this->assertEquals($project_name, $package->getProjectName(), 'Set/got project name.');
    $this->assertEquals($repository_url, $package->getRepositoryUrl(), 'Set/got repository URL.');
    $this->assertEquals($type, $package->getType(), 'Set/got type.');
    $this->assertEquals($version, $package->getVersionRecommended(), 'Set/got recommended version.');
    $this->assertEquals($dev_version, $package->getVersionDev(), 'Set/got dev version.');
    $this->assertEquals($enable, $package->shouldGetEnabled(), 'Determined whether or not should get enabled.');
    $this->assertEquals($package_string, $package->getPackageStringRecommended(), 'Got recommended dependency string.');
    $this->assertEquals($dev_package_string, $package->getPackageStringDev(), 'Got dev dependency string.');
    $this->assertEquals($install_path, $package->getInstallPathRelative(), 'Got relative install path.');
  }

  public function providerPackage() {
    return [
      'Full specification' => [
        'drupal/example_library' => [
          'type' => 'library',
          'install_path' => 'custom/path/to/example_library',
          'url' => '/var/www/example_library',
          'version' => '~1.0',
          'version_dev' => '1.x-dev',
        ],
        'drupal/example_library',
        'example_library',
        'library',
        '/var/www/example_library',
        '~1.0',
        '1.x-dev',
        FALSE,
        'drupal/example_library:~1.0',
        'drupal/example_library:1.x-dev',
        'custom/path/to/example_library',
      ],
      'Minimum specification/default values' => [
        'drupal/example_module' => [
          'version_dev' => '2.x-dev',
        ],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        '*',
        '2.x-dev',
        TRUE,
        'drupal/example_module:*',
        'drupal/example_module:2.x-dev',
        'docroot/modules/contrib/example_module',
      ],
      'Module to not enable' => [
        'drupal/example_module' => [
          'version_dev' => '2.x-dev',
          'enable' => FALSE,
        ],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        '*',
        '2.x-dev',
        FALSE,
        'drupal/example_module:*',
        'drupal/example_module:2.x-dev',
        'docroot/modules/contrib/example_module',
      ],
    ];
  }

  /**
   * @dataProvider providerConstructionError
   */
  public function testConstructionError($exception, $package_name, $data) {
    $this->expectException($exception);

    $this->createPackage($package_name, $data);
  }

  public function providerConstructionError() {
    return [
      'Invalid package name: missing forward slash' => [\InvalidArgumentException::class, 'incomplete', ['version_dev' => '1.x']],
      'Missing "version_dev" property' => [MissingOptionsException::class, 'drupal/example', []],
      'Invalid "enable" value: non-boolean' => [InvalidOptionsException::class, 'drupal/example', ['version_dev' => '1.x', 'enable' => 'invalid']],
      'Unexpected property' => [UndefinedOptionsException::class, 'drupal/example', ['unexpected' => '', 'version_dev' => '1.x'], 'Unexpected property: "unexpected"'],
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
    $package_name = 'drupal/example';
    $data = [
      'type' => $type,
      'version_dev' => '1.x-dev',
    ];

    $package = $this->createPackage($package_name, $data);

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

  protected function createPackage($package_name, $data): Package {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new Package($fixture, $package_name, $data);
  }

}
