<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Domain\Fixture\CodebaseCreator;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Options\FixtureOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Composer\ComposerFacade|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper|\Prophecy\Prophecy\ObjectProphecy $composerJsonHelper
 * @property \Acquia\Orca\Domain\Git\GitFacade|\Prophecy\Prophecy\ObjectProphecy $git
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @coversDefaultClass \Acquia\Orca\Domain\Fixture\CodebaseCreator
 */
class CodebaseCreatorTest extends TestCase {

  private const COMPOSER_JSON = 'composer.json';

  private const COMPOSER_JSON_PATH = 'var/www/orca-build/composer.json';

  protected function setUp(): void {
    $this->composer = $this->prophesize(ComposerFacade::class);
    $this->composerJsonHelper = $this->prophesize(ComposerJsonHelper::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->getPath(self::COMPOSER_JSON)
      ->willReturn(self::COMPOSER_JSON_PATH);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->git = $this->prophesize(GitFacade::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists(Argument::any())
      ->willReturn(TRUE);
  }

  private function createCodebaseCreator(): CodebaseCreator {
    $composer = $this->composer->reveal();
    $composer_json_helper = $this->composerJsonHelper->reveal();
    $fixture = $this->fixture->reveal();
    $git = $this->git->reveal();
    return new CodebaseCreator($composer, $composer_json_helper, $fixture, $git);
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
  public function testCreate($options, $exit_on_patch_failure): void {
    $fixture_options = $this->createFixtureOptions($options);
    $this->composer
      ->createProject($fixture_options)
      ->shouldBeCalledOnce();
    $this->git
      ->ensureFixtureRepo()
      ->shouldBeCalledOnce();
    $this->composerJsonHelper
      ->writeFixtureOptions($fixture_options)
      ->shouldBeCalledOnce();
    $this->composerJsonHelper
      ->set('config.discard-changes', TRUE)
      ->shouldBeCalledOnce();
    $this->composerJsonHelper
      ->set('extra.composer-exit-on-patch-failure', $exit_on_patch_failure)
      ->shouldBeCalledOnce();
    $this->composerJsonHelper
      ->writeFixtureOptions($fixture_options)
      ->shouldBeCalledOnce();

    $creator = $this->createCodebaseCreator();
    $creator->create($fixture_options);
  }

  public function providerCreate(): array {
    return [
      [['dev' => TRUE, 'ignore-patch-failure' => FALSE], TRUE],
      [['dev' => FALSE, 'ignore-patch-failure' => TRUE], FALSE],
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

  public function testCreateWithoutSut(): void {
    $fixture_options = $this->createFixtureOptions([]);
    $this->composer
      ->createProject($fixture_options)
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

  /**
   * @dataProvider providerLoadComposerJsonWithException
   */
  public function testLoadComposerJsonWithException($caught, $thrown): void {
    $fixture_options = $this->createFixtureOptions([]);
    $this->composerJsonHelper
      ->writeFixtureOptions($fixture_options)
      ->shouldBeCalledOnce()
      ->willThrow($caught);
    $this->expectExceptionObject($thrown);

    $creator = $this->createCodebaseCreator();
    $creator->create($fixture_options);
  }

  public function providerLoadComposerJsonWithException(): array {
    return [
      [new OrcaFileNotFoundException(''), new OrcaFileNotFoundException('No such file: ' . self::COMPOSER_JSON_PATH)],
      [new OrcaParseError(''), new OrcaParseError('Cannot parse ' . self::COMPOSER_JSON_PATH)],
    ];
  }

}
