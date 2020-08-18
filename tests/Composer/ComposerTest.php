<?php

namespace Acquia\Orca\Tests\Composer;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixturePathHandler
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Composer\Composer
 */
class ComposerTest extends TestCase {

  private const FIXTURE_PATH = '/var/www';

  protected function setUp(): void {
    $this->fixturePathHandler = $this->prophesize(FixturePathHandler::class);
    $this->fixturePathHandler
      ->getPath()
      ->willReturn(self::FIXTURE_PATH);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::FIXTURE_PATH)
      ->willReturn(0);
  }

  private function createComposer(): Composer {
    $fixture_path_handler = $this->fixturePathHandler->reveal();
    $process_runner = $this->processRunner->reveal();
    return new Composer($fixture_path_handler, $process_runner);
  }

  /**
   * @dataProvider providerCreateProject
   */
  public function testCreateProject(string $project_template_string, string $stability, string $directory): void {
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        "--stability={$stability}",
        $project_template_string,
        $directory,
      ])
      ->shouldBeCalledOnce();

    $composer = $this->createComposer();
    $composer->createProject($project_template_string, $stability, $directory);
  }

  public function providerCreateProject(): array {
    return [
      ['test/example-project1', 'alpha', '/var/www/orca-build1'],
      ['test/example-project2', 'dev', '/var/www/orca-build2'],
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
