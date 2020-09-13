<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Fixture\CodebaseCreator;
use Acquia\Orca\Fixture\FixtureOptions;
use Acquia\Orca\Git\Git;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Package\Package;
use Acquia\Orca\Package\PackageManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Composer\Composer|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Git\Git|\Prophecy\Prophecy\ObjectProphecy $git
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @coversDefaultClass \Acquia\Orca\Fixture\CodebaseCreator
 */
class CodebaseCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(Composer::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->git = $this->prophesize(Git::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists(Argument::any())
      ->willReturn(TRUE);
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

  private function createPackage($data, $package_name): Package {
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new Package($data, $fixture_path_handler, $orca_path_handler, $package_name);
  }

  /**
   * @dataProvider providerCreate
   *
   * @covers ::__construct
   * @covers ::create
   */
  public function testCreate($is_dev): void {
    $fixture_options = $this->createFixtureOptions([
      'dev' => $is_dev,
    ]);
    $this->composer
      ->createProject($fixture_options)
      ->shouldBeCalledOnce();
    $this->git
      ->ensureFixtureRepo()
      ->shouldBeCalledOnce();

    $creator = $this->createCodebaseCreator();
    $creator->create($fixture_options);
  }

  public function providerCreate(): array {
    return [
      [TRUE],
      [FALSE],
    ];
  }

  public function testCreateFromSut(): void {
    $package_name = 'test/example';
    $fixture_options = $this->createFixtureOptions([
      'sut' => $package_name,
    ]);
    $sut = $this->createPackage([
      'type' => 'project-template',
    ], $package_name);
    $this->packageManager
      ->exists($package_name)
      ->willReturn(TRUE);
    $this->packageManager
      ->get($package_name)
      ->willReturn($sut);
    $this->composer
      ->createProjectFromPackage($sut)
      ->shouldBeCalledOnce();

    $creator = $this->createCodebaseCreator();
    $creator->create($fixture_options);
  }

  public function testCreateWithNonProjectTemplateSut(): void {
    $package_name = 'test/example';
    $fixture_options = $this->createFixtureOptions([
      'sut' => $package_name,
    ]);
    $sut = $this->createPackage([], $package_name);
    $this->packageManager
      ->exists($package_name)
      ->willReturn(TRUE);
    $this->packageManager
      ->get($package_name)
      ->willReturn($sut);
    $this->composer
      ->createProject($fixture_options)
      ->shouldBeCalledOnce();

    $creator = $this->createCodebaseCreator();
    $creator->create($fixture_options);
  }

}
