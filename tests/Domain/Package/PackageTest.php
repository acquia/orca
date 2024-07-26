<?php

namespace Acquia\Orca\Tests\Domain\Package;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @coversDefaultClass \Acquia\Orca\Domain\Package\Package
 */
class PackageTest extends TestCase {

  private FixturePathHandler|ObjectProphecy $fixture;
  private OrcaPathHandler|ObjectProphecy $orca;

  public function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
  }

  protected function createPackage($package_name, $data): Package {
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new Package($data, $fixture_path_handler, $orca_path_handler, $package_name);
  }

  /**
   * @dataProvider providerConstructionAndGetters
   *
   * @covers ::__construct
   * @covers ::getDrupalExtensionName
   * @covers ::getInstallPathRelative
   * @covers ::getPackageName
   * @covers ::getProjectName
   * @covers ::getRepositoryUrlAbsolute
   * @covers ::getRepositoryUrlRaw
   * @covers ::getType
   * @covers ::getVersionDev
   * @covers ::getVersionRecommended
   * @covers ::isDrupalExtension
   * @covers ::isDrupalModule
   * @covers ::isDrupalTheme
   * @covers ::shouldGetComposerRequired
   * @covers ::shouldGetEnabled
   */
  public function testConstructionAndGetters($data, $package_name, $project_name, $drupal_extension_name, $type, $raw_repository_url, $install_path, $version, $dev_version, $is_extension, $is_module, $is_theme, $is_project_template, $enable, $require): void {
    $absolute_repository_url = "/var/www/{$raw_repository_url}";
    $this->orca
      ->getPath($raw_repository_url)
      ->willReturn($absolute_repository_url);

    $package = $this->createPackage($package_name, $data);

    self::assertEquals($drupal_extension_name, $package->getDrupalExtensionName(), 'Set/got Drupal extension name.');
    self::assertEquals($install_path, $package->getInstallPathRelative(), 'Set/got relative install path.');
    self::assertEquals($package_name, $package->getPackageName(), 'Set/got package name.');
    self::assertEquals($project_name, $package->getProjectName(), 'Set/got project name.');
    self::assertEquals($absolute_repository_url, $package->getRepositoryUrlAbsolute(), 'Calculated absolute repository URL.');
    self::assertEquals($raw_repository_url, $package->getRepositoryUrlRaw(), 'Set/got raw repository URL.');
    self::assertEquals($type, $package->getType(), 'Set/got type.');
    self::assertEquals($dev_version, $package->getVersionDev(), 'Set/got dev version.');
    self::assertEquals($version, $package->getVersionRecommended(), 'Set/got recommended version.');
    self::assertEquals($is_extension, $package->isDrupalExtension(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals($is_module, $package->isDrupalModule(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals($is_theme, $package->isDrupalTheme(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals($is_project_template, $package->isProjectTemplate(), 'Determined whether or not is a project template.');
    self::assertEquals($require, $package->shouldGetComposerRequired(), 'Determined whether or not should get required with Composer.');
    self::assertEquals($enable, $package->shouldGetEnabled(), 'Determined whether or not should get enabled.');
  }

  public static function providerConstructionAndGetters(): array {
    return [
      'Full specification' => [
        'data' => [
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
        'package_name' => 'drupal/example_library',
        'example_library',
        NULL,
        'library',
        '/var/www/example_library',
        'custom/path/to/example_library',
        '2.x',
        '2.x-dev',
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        FALSE,
        TRUE,
      ],
      'Minimum specification/default values' => [
        'data' => [],
        'package_name' => 'drupal/example_module',
        'example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        'docroot/modules/contrib/example_module',
        '*',
        '*@dev',
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        TRUE,
        TRUE,
      ],
      'Module that should be enabled' => [
        'data' => [
          'version' => NULL,
          'version_dev' => NULL,
        ],
        'package_name' => 'drupal/example_module',
        'example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        'docroot/modules/contrib/example_module',
        NULL,
        NULL,
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        TRUE,
        TRUE,
      ],
      'Module that should not be enabled' => [
        'data' => [
          'enable' => FALSE,
        ],
        'package_name' => 'drupal/example_module',
        'example_module',
        'example_module',
        'drupal-module',
        '../example_module',
        'docroot/modules/contrib/example_module',
        '*',
        '*@dev',
        TRUE,
        TRUE,
        FALSE,
        FALSE,
        FALSE,
        TRUE,
      ],
      'Theme' => [
        'data' => [
          'type' => 'drupal-theme',
        ],
        'package_name' => 'drupal/example_theme',
        'example_theme',
        'example_theme',
        'drupal-theme',
        '../example_theme',
        'docroot/themes/contrib/example_theme',
        '*',
        '*@dev',
        TRUE,
        FALSE,
        TRUE,
        FALSE,
        TRUE,
        TRUE,
      ],
    ];
  }

  public function testProjectTemplate(): void {
    $data = [
      'type' => 'project-template',
    ];
    $this->orca
      ->getPath('../drupal-project')
      ->willReturn('/var/www/drupal-project');

    $package = $this->createPackage('example/drupal-project', $data);

    self::assertEquals(NULL, $package->getDrupalExtensionName(), 'Set/got Drupal extension name.');
    self::assertEquals('.', $package->getInstallPathRelative(), 'Set/got relative install path.');
    self::assertEquals('example/drupal-project', $package->getPackageName(), 'Set/got package name.');
    self::assertEquals('drupal-project', $package->getProjectName(), 'Set/got project name.');
    self::assertEquals('/var/www/drupal-project', $package->getRepositoryUrlAbsolute(), 'Calculated absolute repository URL.');
    self::assertEquals('../drupal-project', $package->getRepositoryUrlRaw(), 'Set/got raw repository URL.');
    self::assertEquals('project-template', $package->getType(), 'Set/got type.');
    self::assertEquals('*@dev', $package->getVersionDev(), 'Set/got dev version.');
    self::assertEquals('*', $package->getVersionRecommended(), 'Set/got recommended version.');
    self::assertEquals(FALSE, $package->isDrupalExtension(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals(FALSE, $package->isDrupalModule(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals(FALSE, $package->isDrupalTheme(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals(TRUE, $package->isProjectTemplate(), 'Determined whether or not is Drupal extensions.');
    self::assertEquals(FALSE, $package->shouldGetComposerRequired(), 'Determined whether or not should get required via Composer.');
    self::assertEquals(FALSE, $package->shouldGetEnabled(), 'Determined whether or not should get enabled.');
  }

  /**
   * @dataProvider providerConstructionError
   *
   * @covers ::initializePackageName
   * @covers ::resolveData
   */
  public function testConstructionError($exception, $package_name, $data): void {
    $this->expectException($exception);

    $this->createPackage($package_name, $data);
  }

  public static function providerConstructionError(): array {
    return [
      'Invalid package name: missing forward slash' => [\InvalidArgumentException::class, 'incomplete', []],
      'Invalid "core_matrix" value: non-array' => [InvalidOptionsException::class, 'drupal/example', ['core_matrix' => 'invalid']],
      'Invalid "enable" value: non-boolean' => [InvalidOptionsException::class, 'drupal/example', ['enable' => 'invalid']],
      'Unexpected root property' => [UndefinedOptionsException::class, 'drupal/example', ['unexpected' => '']],
      'Invalid "core_matrix" constraint' => [\UnexpectedValueException::class, 'drupal/example', ['core_matrix' => ['invalid' => '']]],
      'Invalid "core_matrix" property: non-array' => [\TypeError::class, 'drupal/example', ['core_matrix' => ['8.7.x' => '']]],
      'Unexpected "core_matrix" property' => [UndefinedOptionsException::class, 'drupal/example', ['core_matrix' => ['8.7.x' => ['unexpected' => '']]]],
    ];
  }

  /**
   * @dataProvider providerConditionalVersions
   *
   * @covers ::getVersion
   */
  public function testConditionalVersions($data, $core_version, $version, $version_dev): void {
    $package = $this->createPackage('drupal/example', $data);

    self::assertEquals($version, $package->getVersionRecommended($core_version), 'Got correct recommended version.');
    self::assertEquals($version_dev, $package->getVersionDev($core_version), 'Got correct dev version.');
  }

  public static function providerConditionalVersions(): array {
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
   *
   * @covers ::resolveCoreMatrix
   */
  public function testCoreVersionMatching($expected_to_match, $provided, $required): void {
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
    $is_match = $package->getVersionRecommended($provided) === '2.x';

    self::assertEquals($expected_to_match, $is_match);
  }

  public static function providerCoreVersionMatching(): array {
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

  /**
   * @dataProvider providerExists
   *
   * @covers ::repositoryExists
   */
  public function testExists($url, $expected): void {
    $package = $this->createPackage('drupal/example', [
      'url' => $url,
    ]);
    $this->orca
      ->exists($url)
      ->shouldBeCalledOnce()
      ->willReturn($expected);

    $actual = $package->repositoryExists();

    self::assertEquals($expected, $actual);
  }

  public static function providerExists(): array {
    return [
      ['lorem', TRUE],
      ['ipsum', FALSE],
    ];
  }

  /**
   * @dataProvider providerInstallPathCalculation
   *
   * @covers ::getInstallPathRelative
   * @covers ::getInstallPathAbsolute
   */
  public function testInstallPathCalculation($type, $relative_install_path): void {
    $absolute_install_path = "/var/www/{$relative_install_path}";
    $this->fixture
      ->getPath($relative_install_path)
      ->willReturn($absolute_install_path);
    $package_name = 'drupal/example';
    $data = [
      'type' => $type,
    ];

    $package = $this->createPackage($package_name, $data);

    self::assertEquals($relative_install_path, $package->getInstallPathRelative());
    self::assertEquals($absolute_install_path, $package->getInstallPathAbsolute());
  }

  public static function providerInstallPathCalculation(): array {
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

}
