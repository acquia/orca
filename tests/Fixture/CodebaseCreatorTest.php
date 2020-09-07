<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Fixture\CodebaseCreator;
use Acquia\Orca\Fixture\FixtureOptions;
use Acquia\Orca\Git\Git;
use Acquia\Orca\Package\PackageManager;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Composer\Composer|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Git\Git|\Prophecy\Prophecy\ObjectProphecy $git
 * @property \Acquia\Orca\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @coversDefaultClass \Acquia\Orca\Fixture\CodebaseCreator
 */
class CodebaseCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(Composer::class);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->git = $this->prophesize(Git::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  private function createCodebaseCreator(): CodebaseCreator {
    $composer = $this->composer->reveal();
    $git = $this->git->reveal();
    return new CodebaseCreator($composer, $git);
  }

  private function createFixtureOptions($options): FixtureOptions {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new FixtureOptions($drupal_core_version_finder, $package_manager, $options);
  }

  /**
   * @dataProvider providerCreateDefaults
   *
   * @covers ::__construct
   * @covers ::create
   * @covers ::createProject
   */
  public function testCreateDefaults($project_template_string, $is_dev, $stability): void {
    $this->composer
      ->createProject($project_template_string, $stability)
      ->shouldBeCalledOnce();
    $this->git
      ->ensureFixtureRepo()
      ->shouldBeCalledOnce();
    $fixture_options = $this->createFixtureOptions(['dev' => $is_dev]);

    $creator = $this->createCodebaseCreator();
    $creator->create($fixture_options, $project_template_string);
  }

  public function providerCreateDefaults(): array {
    return [
      ['test/example-project1', TRUE, 'dev'],
      ['test/example-project2', FALSE, 'alpha'],
    ];
  }

}
