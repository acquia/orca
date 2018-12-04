<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Project;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Acquia\Orca\Fixture\Project
 */
class ProjectTest extends TestCase {

  /**
   * @dataProvider providerProject
   */
  public function testProject($data, $package_name, $project_name, $repository_url, $type, $version, $package_string, $install_path) {
    $project = new Project($data);

    $this->assertInstanceOf(Project::class, $project, 'Instantiated class.');
    $this->assertEquals($package_name, $project->getPackageName(), 'Set/got package name.');
    $this->assertEquals($project_name, $project->getProjectName(), 'Set/got project name.');
    $this->assertEquals($repository_url, $project->getRepositoryUrl(), 'Set/got repository URL.');
    $this->assertEquals($type, $project->getType(), 'Set/got type.');
    $this->assertEquals($version, $project->getVersion(), 'Set/got version.');
    $this->assertEquals($package_string, $project->getPackageString(), 'Got dependency string.');
    $this->assertEquals($install_path, $project->getInstallPathRelative(), 'Got relative install path.');
  }

  public function providerProject() {
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
        'docroot/modules/contrib/acquia/example_module',
      ],
    ];
  }

  /**
   * @dataProvider providerConstructionError
   */
  public function testConstructionError($data, $message) {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);

    new Project($data);
  }

  /**
   * @dataProvider providerInstallPathCalculation
   */
  public function testInstallPathCalculation($type, $install_path) {
    $data = [
      'name' => 'drupal/example',
      'type' => $type,
    ];

    $project = new Project($data);

    $this->assertEquals($install_path, $project->getInstallPathRelative());
  }

  public function providerInstallPathCalculation() {
    return [
      ['drupal-module', 'docroot/modules/contrib/acquia/example'],
      ['drupal-drush', 'drush/Commands/example'],
      ['drupal-theme', 'docroot/themes/contrib/acquia/example'],
      ['drupal-profile', 'docroot/profiles/contrib/acquia/example'],
      ['drupal-library', 'docroot/libraries/example'],
      ['bower-asset', 'docroot/libraries/example'],
      ['npm-asset', 'docroot/libraries/example'],
      ['something-nonstandard', 'vendor/drupal/example'],
    ];
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

}
