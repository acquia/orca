<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use UnexpectedValueException;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @covers \Acquia\Orca\Fixture\Package
 */
class PackageTest extends TestCase {

  private $projectDir = '../example';

  public function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
  }

  /**
   * @dataProvider providerConstruction
   */
  public function testConstruction($data, $package_name, $project_name, $type, $repository_url, $version, $dev_version, $enable, $install_path) {
    $package = $this->createPackage($package_name, $data);

    $this->assertInstanceOf(Package::class, $package, 'Instantiated class.');
    $this->assertEquals($package_name, $package->getPackageName(), 'Set/got package name.');
    $this->assertEquals($project_name, $package->getProjectName(), 'Set/got project name.');
    $this->assertEquals($repository_url, $package->getRepositoryUrlRaw(), 'Set/got repository URL.');
    $this->assertEquals($type, $package->getType(), 'Set/got type.');
    $this->assertEquals($version, $package->getVersionRecommended(), 'Set/got recommended version.');
    $this->assertEquals($dev_version, $package->getVersionDev(), 'Set/got dev version.');
    $this->assertEquals($enable, $package->shouldGetEnabled(), 'Determined whether or not should get enabled.');
    $this->assertEquals($install_path, $package->getInstallPathRelative(), 'Got relative install path.');
  }

  public function providerConstruction() {
    return [
      'Full specification' => [
        'drupal/example_library' => [
          'type' => 'library',
          'install_path' => 'custom/path/to/example_library',
          'url' => '/var/www/example_library',
          'version' => '2.x',
          'version_dev' => '2.x-dev',
          'core_matrix' => [
            '<8.7.0' => [
              'version' => '1.x',
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        'drupal/example_library',
        'example_library',
        'library',
        '/var/www/example_library',
        '2.x',
        '2.x-dev',
        FALSE,
        'custom/path/to/example_library',
      ],
      'Minimum specification/default values' => [
        'drupal/example_module' => [],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        '*',
        '*@dev',
        TRUE,
        'docroot/modules/contrib/example_module',
      ],
      'Module to not install' => [
        'drupal/example_module' => [
          'version' => NULL,
          'version_dev' => NULL,
        ],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        NULL,
        NULL,
        TRUE,
        'docroot/modules/contrib/example_module',
      ],
      'Module to not enable' => [
        'drupal/example_module' => [
          'enable' => FALSE,
        ],
        'drupal/example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        '*',
        '*@dev',
        FALSE,
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
      'Invalid package name: missing forward slash' => [\InvalidArgumentException::class, 'incomplete', []],
      'Invalid "core_matrix" value: non-array' => [InvalidOptionsException::class, 'drupal/example', ['core_matrix' => 'invalid']],
      'Invalid "enable" value: non-boolean' => [InvalidOptionsException::class, 'drupal/example', ['enable' => 'invalid']],
      'Unexpected root property' => [UndefinedOptionsException::class, 'drupal/example', ['unexpected' => '']],
      'Invalid "core_matrix" constraint' => [UnexpectedValueException::class, 'drupal/example', ['core_matrix' => ['invalid' => '']]],
      'Invalid "core_matrix" property: non-array' => [\TypeError::class, 'drupal/example', ['core_matrix' => ['8.7.x' => '']]],
      'Unexpected "core_matrix" property' => [UndefinedOptionsException::class, 'drupal/example', ['core_matrix' => ['8.7.x' => ['unexpected' => '']]]],
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

  /**
   * @dataProvider providerConditionalVersions
   */
  public function testConditionalVersions($data, $core_version, $version, $version_dev) {
    $package = $this->createPackage('drupal/example', $data);

    $this->assertEquals($version, $package->getVersionRecommended($core_version), 'Got correct recommended version.');
    $this->assertEquals($version_dev, $package->getVersionDev($core_version), 'Got correct dev version.');
  }

  public function providerConditionalVersions() {
    return [
      'Empty (defaults), no core version' => [
        [],
        NULL,
        '*',
        '*@dev',
      ],
      'Empty (defaults) with core version' => [
        [],
        '8.7.0',
        '*',
        '*@dev',
      ],
      'Matrix only, no core version' => [
        [
          'core_matrix' => [
            '8.8.x' => [
              'version' => '2.x',
              'version_dev' => '2.x-dev',
            ],
          ],
        ],
        NULL,
        '*',
        '*@dev',
      ],
      'Matrix only with core version, no match' => [
        [
          'core_matrix' => [
            '8.8.x' => [
              'version' => '2.x',
              'version_dev' => '2.x-dev',
            ],
          ],
        ],
        '8.7.0',
        '*',
        '*@dev',
      ],
      'Matrix only with core version and match' => [
        [
          'core_matrix' => [
            '8.7.x' => [
              'version' => '1.x',
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        '8.7.0',
        '1.x',
        '1.x-dev',
      ],
      'Matrix with NULL version values' => [
        [
          'core_matrix' => [
            '8.7.x' => [
              'version' => NULL,
              'version_dev' => NULL,
            ],
          ],
        ],
        '8.7.0',
        NULL,
        NULL,
      ],
      'Static only' => [
        [
          'version' => '1.x',
          'version_dev' => '1.x-dev',
        ],
        NULL,
        '1.x',
        '1.x-dev',
      ],
      'Static only with core version' => [
        [
          'version' => '1.x',
          'version_dev' => '1.x-dev',
        ],
        '8.7.0',
        '1.x',
        '1.x-dev',
      ],
      'Both, no core version' => [
        [
          'version' => '2.x',
          'version_dev' => '2.x-dev',
          'core_matrix' => [
            '8.6.x' => [
              'version' => '1.x',
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        NULL,
        '2.x',
        '2.x-dev',
      ],
      'Both with core version, no matches' => [
        [
          'version' => '2.x',
          'version_dev' => '2.x-dev',
          'core_matrix' => [
            '8.6.x' => [
              'version' => '1.x',
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        '8.7.0',
        '2.x',
        '2.x-dev',
      ],
      'Both with core version, one match' => [
        [
          'version' => '2.x',
          'version_dev' => '2.x-dev',
          'core_matrix' => [
            '8.7.x' => [
              'version' => '1.x',
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        '8.7.0',
        '1.x',
        '1.x-dev',
      ],
      'Multiple matches' => [
        [
          'core_matrix' => [
            '8.8.x' => [
              'version' => '3.x',
              'version_dev' => '3.x-dev',
            ],
            '8.7.x' => [
              'version' => '2.x',
              'version_dev' => '2.x-dev',
            ],
            '*' => [
              'version' => '1.x',
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        '8.7.0',
        '2.x',
        '2.x-dev',
      ],
      'Match providing only recommended version' => [
        [
          'core_matrix' => [
            '8.7.x' => [
              'version' => '1.x',
            ],
          ],
        ],
        '8.7.0',
        '1.x',
        '*@dev',
      ],
      'Match providing only dev version' => [
        [
          'core_matrix' => [
            '8.7.x' => [
              'version_dev' => '1.x-dev',
            ],
          ],
        ],
        '8.7.0',
        '*',
        '1.x-dev',
      ],
    ];
  }

  /**
   * @dataProvider providerCoreVersionMatching
   */
  public function testCoreVersionMatching($expected_to_match, $provided, $required) {
    $package = $this->createPackage('drupal/example', [
      'core_matrix' => [
        $required => [
          'version' => '2.x',
        ],
      ],
    ]);

    // The version from the core matrix (2.x) will only be returned if the
    // provided core version matches the requirement, so it serves as a good
    // test of a match.
    $is_match = $package->getVersionRecommended($provided) == '2.x';

    $this->assertEquals($expected_to_match, $is_match);
  }

  public function providerCoreVersionMatching() {
    return [
      // Matches.
      [TRUE, '8.7.0', '8.7.0'],
      [TRUE, '8.7.0', '8.7.x'],
      [TRUE, '8.7.0', '~8.7'],
      [TRUE, '8.7.0', '<8.8.0'],
      [TRUE, '8.7.0', '>=8.7.0 <8.8.0'],
      [TRUE, '8.7.0', '*'],
      [TRUE, '8.7.0', '*@dev'],
      [TRUE, '8.7.x-dev', '~8.7.0'],
      [TRUE, '8.7.x-dev', '*@dev'],
      // Mismatches.
      [FALSE, '8.7.0', '8.8.0'],
      [FALSE, '8.7.0', '8.8.x'],
      [FALSE, '8.7.0', '<8.6.0'],
      [FALSE, '8.7.0', '>=8.8.0'],
      [FALSE, '8.7.x-dev', '~8.6.0'],
      [FALSE, '8.7.x-dev', '~8.8.0'],
    ];
  }

  protected function createPackage($package_name, $data): Package {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new Package($data, $fixture, $package_name, $this->projectDir);
  }

}
