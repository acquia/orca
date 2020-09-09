<?php

namespace Acquia\Orca\Tests\Composer;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Composer\VersionGuesser;
use Acquia\Orca\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Fixture\FixtureOptions;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Package\Package;
use Acquia\Orca\Package\PackageManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Composer\VersionGuesser|\Prophecy\Prophecy\ObjectProphecy $versionGuesser
 * @property \Acquia\Orca\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Acquia\Orca\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @coversDefaultClass \Acquia\Orca\Composer\Composer
 */
class ComposerTest extends TestCase {

  private const FIXTURE_PATH = '/var/www/orca-build';

  protected function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->getPath()
      ->willReturn(self::FIXTURE_PATH);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::FIXTURE_PATH)
      ->willReturn(0);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any())
      ->willReturn(0);
    $this->versionGuesser = $this->prophesize(VersionGuesser::class);
  }

  private function createComposer(): Composer {
    $fixture_path_handler = $this->fixture->reveal();
    $package_manager = $this->packageManager->reveal();
    $process_runner = $this->processRunner->reveal();
    $version_guesser = $this->versionGuesser->reveal();
    return new Composer($fixture_path_handler, $package_manager, $process_runner, $version_guesser);
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
   */
  public function testCreateProjectWithoutSut($options, $stability, $project_template_string): void {
    $options = $this->createFixtureOptions($options);

    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        "--stability={$stability}",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        $project_template_string,
        self::FIXTURE_PATH,
      ])
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

      'BLT project/dev stability' => [
        'options' => [
          'project-template' => 'acquia/blt-project',
          'dev' => TRUE,
        ],
        'stability' => 'dev',
        'project_template_string' => 'acquia/blt-project',
      ],
    ];
  }

  public function testCreateProjectWithProjectTemplateSut(): void {
    $project_template = 'test/example';
    $guess = '9999999-dev';
    $package = $this->createPackage([
      'type' => 'project-template',
    ], $project_template);
    $this->packageManager
      ->exists($project_template)
      ->willReturn(TRUE);
    $this->packageManager
      ->get($project_template)
      ->willReturn($package);
    $this->versionGuesser
      ->guessVersion(Argument::any())
      ->willReturn($guess);
    $options = $this->createFixtureOptions([
      'project-template' => $project_template,
      'sut' => $project_template,
    ]);
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        '--stability=alpha',
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        "{$project_template}:{$guess}",
        self::FIXTURE_PATH,
      ])
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->createProject($options);
  }

  public function testCreateProjectWithNonProjectTemplateSut(): void {
    $package_name = 'test/example';
    $package = $this->createPackage([], $package_name);
    $this->packageManager
      ->exists($package_name)
      ->willReturn(TRUE);
    $this->packageManager
      ->get($package_name)
      ->willReturn($package);
    $this->drupalCoreVersionFinder
      ->get(Argument::any())
      ->willReturn('1.0.0');
    $project_template = 'test/example-project';
    $options = $this->createFixtureOptions([
      'project-template' => $project_template,
      'sut' => $package_name,
    ]);
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        "--stability=alpha",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        $project_template,
        self::FIXTURE_PATH,
      ])
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->createProject($options);
  }

  /**
   * @dataProvider providerCreateProjectBlt
   * @covers ::createProject
   * @covers ::getBltProjectVersion
   * @covers ::getProjectTemplateString
   */
  public function testCreateProjectBlt($is_dev, $stability, $version): void {
    $blt = $this->prophesize(Package::class);
    $blt->isProjectTemplate()
      ->willReturn(TRUE);
    $blt->getVersionDev(Argument::any())
      ->shouldBeCalledTimes((bool) $is_dev)
      ->willReturn($version);
    $blt->getVersionRecommended(Argument::any())
      ->shouldBeCalledTimes(!$is_dev)
      ->willReturn($version);
    $this->packageManager
      ->exists(Argument::any())
      ->willReturn(TRUE);
    $this->packageManager
      ->get(Argument::any())
      ->willReturn($blt);
    $this->packageManager
      ->getBlt()
      ->willReturn($blt);
    $this->drupalCoreVersionFinder
      ->get(Argument::any())
      ->willReturn($version);
    $project_template = 'acquia/blt-project';
    $options = $this->createFixtureOptions([
      'dev' => $is_dev,
      'project-template' => $project_template,
      'sut' => $project_template,
    ]);
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        "--stability={$stability}",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        "{$project_template}:{$version}",
        self::FIXTURE_PATH,
      ])
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->createProject($options);
  }

  public function providerCreateProjectBlt(): array {
    return [
      ['is_dev' => TRUE, 'stability' => 'dev', 'version' => '1.x-dev'],
      ['is_dev' => FALSE, 'stability' => 'alpha', 'version' => '1.x'],
    ];
  }

  /**
   * @dataProvider providerCreateProject
   */
  public function testCreateProject(string $project_template, $is_dev, string $stability, string $directory): void {
    $this->fixture
      ->getPath()
      ->shouldBeCalledOnce()
      ->willReturn($directory);
    $options = $this->createFixtureOptions([
      'dev' => $is_dev,
      'project-template' => $project_template,
    ]);
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        "--stability={$stability}",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        $project_template,
        $directory,
      ])
      ->shouldBeCalledOnce();

    $composer = $this->createComposer();
    $composer->createProject($options);
  }

  public function providerCreateProject(): array {
    return [
      ['test/example1', FALSE, 'alpha', '/var/www/orca-build1'],
      ['test/example2', TRUE, 'dev', '/var/www/orca-build2'],
    ];
  }

  /**
   * @dataProvider providerCreateProjectFromPackage
   */
  public function testCreateProjectFromPackage($package_name, $repository_url, $guess, $directory): void {
    $package = $this->prophesize(Package::class);
    $package->getPackageName()
      ->willReturn($package_name);
    $package->getRepositoryUrlAbsolute()
      ->willReturn($repository_url);
    $this->versionGuesser
      ->guessVersion(Argument::any())
      ->willReturn($guess);
    $this->fixture
      ->getPath()
      ->shouldBeCalledOnce()
      ->willReturn($directory);
    $repository = json_encode([
      'type' => 'path',
      'url' => $repository_url,
      'options' => [
        'symlink' => FALSE,
      ],
    ]);
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        '--stability=dev',
        "--repository={$repository}",
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        "{$package_name}:{$guess}",
        $directory,
      ])
      ->shouldBeCalledOnce();

    $composer = $this->createComposer();
    $composer->createProjectFromPackage($package->reveal());
  }

  public function providerCreateProjectFromPackage(): array {
    return [
      ['example/drupal-recommended-project', '/var/www/drupal-recommended-project', 'dev-develop', '/var/www/orca-build1'],
      ['example/drupal-minimal-project', '/var/www/drupal-minimal-project', '9999999-dev', '/var/www/orca-build2'],
    ];
  }

  /**
   * @dataProvider providerIsValidPackageName
   *
   * @covers ::isValidPackageName
   */
  public function testIsValidPackageName($expected, $name): void {
    self::assertEquals($expected, Composer::isValidPackageName($name));
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
    $actual = Composer::isValidVersionConstraint($version);

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
      ->runOrcaVendorBin(array_merge([
        'composer',
        'remove',
        '--no-update',
      ], $packages), self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->removePackages($packages);
  }

  public function testRemovePackagesEmptyArray(): void {
    $this->expectException(InvalidArgumentException::class);
    $composer = $this->createComposer();

    $composer->removePackages([]);
  }

  /**
   * @dataProvider providerPackageList
   */
  public function testRequirePackages(array $packages): void {
    $this->processRunner
      ->runOrcaVendorBin(array_merge([
        'composer',
        'require',
        '--no-interaction',
      ], $packages), self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $composer = $this->createComposer();

    $composer->requirePackages($packages);
  }

  public function testRequirePackagesEmptyArray(): void {
    $this->expectException(InvalidArgumentException::class);
    $composer = $this->createComposer();

    $composer->requirePackages([]);
  }

  public function testUpdateLockFile(): void {
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
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
