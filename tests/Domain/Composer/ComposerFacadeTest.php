<?php

namespace Acquia\Orca\Tests\Domain\Composer;

use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\FixtureOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Domain\Package\Package|\Prophecy\Prophecy\ObjectProphecy $blt
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\ComposerFacade
 */
class ComposerFacadeTest extends TestCase {

  private const FIXTURE_PATH = '/var/www/orca-build';

  private const ORCA_PATH = '/var/www/orca';

  private const PACKAGE_ABSOLUTE_PATH = '/var/www/example';

  protected function setUp(): void {
    $this->blt = $this->prophesize(Package::class);
    $this->blt->getPackageName()
      ->willReturn('acquia/blt-project');
    $this->blt->isProjectTemplate()
      ->willReturn(TRUE);
    $this->blt->getRepositoryUrlAbsolute()
      ->willReturn(self::PACKAGE_ABSOLUTE_PATH);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->getPath()
      ->willReturn(self::FIXTURE_PATH);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(Argument::any())
      ->willReturn(self::ORCA_PATH);
    $this->orca
      ->getPath()
      ->willReturn(self::ORCA_PATH);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists('acquia/blt-project')
      ->willReturn(TRUE);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runExecutable(Argument::any(), Argument::any(), Argument::any())
      ->willReturn(0);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::FIXTURE_PATH)
      ->willReturn(0);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any())
      ->willReturn(0);
  }

  private function createComposer(): ComposerFacade {
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $package_manager = $this->packageManager->reveal();
    $process_runner = $this->processRunner->reveal();
    return new ComposerFacade($fixture_path_handler, $orca_path_handler, $package_manager, $process_runner);
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
   * @dataProvider providerCreateProjectWithoutSut
   *
   * @covers ::createProject
   * @covers ::getProjectTemplateString
   */
  public function testCreateProjectWithoutSut($options, $stability, $project_template_string): void {
    $options = $this->createFixtureOptions($options);

    $this->processRunner
      ->runExecutable('composer', [
        'create-project',
        "--stability={$stability}",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        $project_template_string,
        self::FIXTURE_PATH,
      ], self::ORCA_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->createProject($options);
  }

  public function providerCreateProjectWithoutSut(): array {
    return [
      'Arbitrary project template/recommended stability' => [
        'options' => [
          'project-template' => 'test/example1',
          'dev' => FALSE,
        ],
        'stability' => 'alpha',
        'project_template_string' => 'test/example1',
      ],

      'Arbitrary project template/dev stability' => [
        'options' => [
          'project-template' => 'test/example2',
          'dev' => TRUE,
        ],
        'stability' => 'dev',
        'project_template_string' => 'test/example2',
      ],
    ];
  }

  /**
   * @covers ::createProject
   * @covers ::getProjectTemplateString
   */
  public function testCreateProjectWithNonProjectTemplateSut(): void {
    $package_name = 'test/example';
    $package = $this->createPackage([], $package_name);
    $this->packageManager
      ->exists($package_name)
      ->willReturn(TRUE);
    $this->packageManager
      ->get($package_name)
      ->willReturn($package);
    $project_template = 'test/example-project';
    $options = $this->createFixtureOptions([
      'project-template' => $project_template,
      'sut' => $package_name,
    ]);
    $this->processRunner
      ->runExecutable('composer', [
        'create-project',
        "--stability=alpha",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        $project_template,
        self::FIXTURE_PATH,
      ], self::ORCA_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->createProject($options);
  }

  /**
   * @dataProvider providerCreateProjectFromPackage
   */
  public function testCreateProjectFromPackage($package_name, $repository_url, $directory): void {
    $package = $this->prophesize(Package::class);
    $package->getPackageName()
      ->willReturn($package_name);
    $package->getRepositoryUrlAbsolute()
      ->willReturn($repository_url);
    $this->fixture
      ->getPath()
      ->shouldBeCalledOnce()
      ->willReturn($directory);
    $repository = json_encode([
      'type' => 'path',
      'url' => $repository_url,
      'options' => [
        'symlink' => FALSE,
        'canonical' => TRUE,
      ],
    ]);
    $this->processRunner
      ->runExecutable('composer', [
        'create-project',
        '--stability=dev',
        "--repository={$repository}",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        $package_name,
        $directory,
      ], self::ORCA_PATH)
      ->shouldBeCalledOnce();

    $composer = $this->createComposer();
    $composer->createProjectFromPackage($package->reveal());
  }

  public function providerCreateProjectFromPackage(): array {
    return [
      [
        'package_name' => 'example/drupal-recommended-project',
        'repository_url' => '/var/www/drupal-recommended-project',
        'directory' => '/var/www/orca-build1',
      ],
      [
        'package_name' => 'example/drupal-minimal-project',
        'repository_url' => '/var/www/drupal-minimal-project',
        'directory' => '/var/www/orca-build2',
      ],
    ];
  }

  /**
   * @dataProvider providerIsValidPackageName
   *
   * @covers ::isValidPackageName
   */
  public function testIsValidPackageName($expected, $name): void {
    self::assertEquals($expected, ComposerFacade::isValidPackageName($name));
  }

  public function providerIsValidPackageName(): array {
    return [
      [TRUE, 'test/example'],
      [TRUE, 'lorem_ipsum/dolor_sit'],
      [TRUE, 'lorem-ipsum/dolor-sit'],
      [FALSE, ''],
      [FALSE, '/'],
      [FALSE, 'ab'],
      [FALSE, 'test/example '],
      [FALSE, 'test / example'],
      [FALSE, '_test/example_'],
    ];
  }

  /**
   * @dataProvider providerIsValidVersionConstraint
   */
  public function testIsValidConstraint($expected, $version): void {
    $actual = ComposerFacade::isValidVersionConstraint($version);

    self::assertEquals($expected, $actual, 'Correctly determined validity of version constraint.');
  }

  public function providerIsValidVersionConstraint(): array {
    return [
      [TRUE, '^1.0'],
      [TRUE, '~1.0'],
      [TRUE, '>=1.0'],
      [TRUE, 'dev-topic-branch'],
      [FALSE, 'garbage'],
      [FALSE, '1.0.x-garbage'],
    ];
  }

  /**
   * @dataProvider providerPackageList
   */
  public function testRemovePackages(array $packages): void {
    $this->processRunner
      ->runOrcaVendorBin(Argument::any())
      ->willReturn(0);
    $this->processRunner
      ->runExecutable('composer', array_merge([
        'remove',
        '--no-update',
      ], $packages), self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->removePackages($packages);
  }

  public function testRemovePackagesEmptyArray(): void {
    $this->expectException(\InvalidArgumentException::class);
    $composer = $this->createComposer();

    $composer->removePackages([]);
  }

  /**
   * @dataProvider providerPackageList
   */
  public function testRequirePackages(array $packages): void {
    $this->processRunner
      ->runExecutable('composer', array_merge([
        'require',
        '--no-interaction',
      ], $packages), self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->requirePackages($packages);
  }

  /**
   * @dataProvider providerRequirePackagesOptions
   */
  public function testRequirePackagesOptions($prefer_source, $no_update, $options): void {
    $options[] = '--no-interaction';
    $packages = ['test1/example1', 'test2/example2'];
    $command = array_merge([
      'require',
    ], $options, $packages);
    $this->processRunner
      ->runExecutable('composer', $command, self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->requirePackages($packages, $prefer_source, $no_update);
  }

  public function providerRequirePackagesOptions(): array {
    return [
      [
        'prefer_source' => FALSE,
        'no_update' => FALSE,
        'options' => [],
      ],
      [
        'prefer_source' => FALSE,
        'no_update' => FALSE,
        'options' => [],
      ],
      [
        'prefer_source' => TRUE,
        'no_update' => FALSE,
        'options' => [
          '--prefer-source',
        ],
      ],
      [
        'prefer_source' => FALSE,
        'no_update' => TRUE,
        'options' => [
          '--no-update',
        ],
      ],
      [
        'prefer_source' => TRUE,
        'no_update' => TRUE,
        'options' => [
          '--prefer-source',
          '--no-update',
        ],
      ],
      [
        'prefer_source' => NULL,
        'no_update' => NULL,
        'options' => [],
      ],
    ];
  }

  /**
   * @see https://github.com/acquia/orca/pull/113
   */
  public function testRequirePackagesEmptyArray(): void {
    $this->processRunner
      ->runExecutable('composer', array_merge([
        'require',
        '--no-interaction',
      ], []), self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->requirePackages([]);
  }

  public function testUpdateLockFile(): void {
    $this->processRunner
      ->runExecutable('composer', [
        'update',
        '--lock',
      ], self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->updateLockFile();
  }

  public function providerPackageList(): array {
    return [
      [['test/example']],
      [['test1/example1'], ['test2/example2']],
      [['test2/example2'], ['test3/example3'], ['test4/example4']],
    ];
  }

}
