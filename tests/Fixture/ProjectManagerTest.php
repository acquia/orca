<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Project;
use Acquia\Orca\Fixture\ProjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;

/**
 * @covers \Acquia\Orca\Fixture\ProjectManager
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Yaml\Parser $parser
 */
class ProjectManagerTestTest extends TestCase {

  private const PROJECTS_DATA = [
    ['name' => 'drupal/module1'],
    ['name' => 'drupal/module2', 'version' => '~1.0'],
    ['name' => 'drupal/drush1', 'type' => 'drupal-drush'],
    ['name' => 'drupal/drush2', 'type' => 'drupal-drush'],
    ['name' => 'drupal/theme1', 'type' => 'drupal-theme'],
    ['name' => 'drupal/theme2', 'type' => 'drupal-theme'],
  ];

  private const ALL_PROJECTS = [
    'drupal/module1',
    'drupal/module2',
    'drupal/drush1',
    'drupal/drush2',
    'drupal/theme1',
    'drupal/theme2',
  ];

  private const ALL_PROJECT_VERSIONS = [
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

  private $projectsConfig = 'config/projects.yml';

  protected function setUp() {
    $this->parser = $this->prophesize(Parser::class);
    $this->parser
      ->parseFile('/var/www/orca/config/projects.yml')
      ->shouldBeCalledTimes(1)
      ->willReturn(self::PROJECTS_DATA);
  }

  public function testProjectManager() {
    $manager = $this->createProjectManager();
    $all_projects = $manager->getMultiple();
    $all_project_versions = $manager->getMultiple(NULL, 'getVersion');
    $modules = $manager->getMultiple('drupal-module');
    $module_repository_urls = $manager->getMultiple('drupal-module', 'getRepositoryUrl');
    $themes = $manager->getMultiple('drupal-theme');
    $project = $manager->get('drupal/module2');

    $this->assertEquals(self::ALL_PROJECTS, array_keys($all_projects), 'Set/got all projects.');
    $this->assertInstanceOf(Project::class, reset($all_projects));
    $this->assertEquals(self::ALL_PROJECT_VERSIONS, $all_project_versions);
    $this->assertEquals(self::MODULES, array_keys($modules), 'Got modules.');
    $this->assertInstanceOf(Project::class, reset($modules));
    $this->assertEquals(self::MODULE_DIR_BASENAMES, $module_repository_urls, 'Got module repository URLs.');
    $this->assertEquals(self::THEMES, array_keys($themes), 'Got themes.');
    $this->assertInstanceOf(Project::class, reset($themes));
    $this->assertEquals('drupal/module2', $project->getPackageName(), 'Got project by name.');
    $this->assertInstanceOf(Project::class, $project);
  }

  public function testRequestingNonExistentProject() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('No such package: nonexistent/package');

    $manager = $this->createProjectManager();
    $manager->get('nonexistent/package');
  }

  /**
   * @dataProvider providerCheckingProjectExistence
   */
  public function testCheckingProjectExistence($package_name, $expected) {
    $manager = $this->createProjectManager();
    $actual = $manager->exists($package_name);

    $this->assertEquals($expected, $actual, 'Correctly tested for project existence.');
  }

  public function providerCheckingProjectExistence() {
    return [
      ['drupal/module1', TRUE],
      ['nonexistent/package', FALSE],
    ];
  }

  /**
   * @return \Acquia\Orca\Fixture\ProjectManager
   */
  private function createProjectManager(): ProjectManager {
    /** @var \Symfony\Component\Yaml\Parser $parser */
    $parser = $this->parser->reveal();
    $object = new ProjectManager($parser, $this->projectsConfig, $this->projectDir);
    $this->assertInstanceOf(ProjectManager::class, $object, 'Instantiated class.');
    return $object;
  }

}
