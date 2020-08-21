<?php

namespace Acquia\Orca\Tests\Composer;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Exception\FileNotFoundException as OrcaFileNotFoundExceptionAlias;
use Acquia\Orca\Helper\Exception\OrcaException;
use Acquia\Orca\Helper\Exception\ParseError;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Composer\Package\Version\VersionGuesser;
use Exception;
use InvalidArgumentException;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundExceptionAlias;
use Noodlehaus\Exception\ParseException;
use Noodlehaus\Parser\Json;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixturePathHandler
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Composer\Package\Version\VersionGuesser|\Prophecy\Prophecy\ObjectProphecy $versionGuesser
 * @coversDefaultClass \Acquia\Orca\Composer\Composer
 */
class ComposerTest extends TestCase {

  private const FIXTURE_PATH = '/var/www';

  protected function setUp(): void {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->fixturePathHandler = $this->prophesize(FixturePathHandler::class);
    $this->fixturePathHandler
      ->getPath()
      ->willReturn(self::FIXTURE_PATH);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::FIXTURE_PATH)
      ->willReturn(0);
    $this->versionGuesser = $this->prophesize(VersionGuesser::class);
  }

  private function createComposer(): Composer {
    $config_loader = $this->configLoader->reveal();
    $fixture_path_handler = $this->fixturePathHandler->reveal();
    $process_runner = $this->processRunner->reveal();
    $version_guesser = $this->versionGuesser->reveal();
    return new Composer($config_loader, $fixture_path_handler, $process_runner, $version_guesser);
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
   * @dataProvider providerGuessVersion
   * @covers ::guessVersion
   */
  public function testGuessVersion($path, $guess, $expected): void {
    $data = ['test' => 'example'];
    $json = json_encode($data);
    $config = new Config($json, new Json(), TRUE);
    $this->configLoader
      ->load("{$path}/composer.json")
      ->shouldBeCalledOnce()
      ->willReturn($config);
    $this->versionGuesser
      ->guessVersion($data, $path)
      ->shouldBeCalledOnce()
      ->willReturn($guess);

    $composer = $this->createComposer();
    $actual = $composer->guessVersion($path);

    self::assertEquals($expected, $actual, 'Returned correct version string.');
  }

  public function providerGuessVersion(): array {
    return [
      ['/var/www/package1', ['version' => '9999999-dev'], '9999999-dev'],
      ['/var/www/package2', ['version' => 'dev-topic-branch'], 'dev-topic-branch'],
      ['/var/www/package3', [], '@dev'],
    ];
  }

  /**
   * @dataProvider providerGuessVersionWithException
   *
   * @covers ::guessVersion
   */
  public function testGuessVersionWithException($caught, $thrown): void {
    $path = '/path';
    $composer_json_path = "{$path}/composer.json";
    $this->configLoader
      ->load($composer_json_path)
      ->shouldBeCalledOnce()
      ->willThrow($caught);
    $this->expectExceptionObject($thrown);

    $composer = $this->createComposer();
    $composer->guessVersion($path);
  }

  public function providerGuessVersionWithException() {
    return [
      [new NoodlehausFileNotFoundExceptionAlias(''), new OrcaFileNotFoundExceptionAlias('No such file: /path/composer.json')],
      [new ParseException(['message' => '']), new ParseError('Cannot parse /path/composer.json')],
      [new Exception(''), new OrcaException('Unknown error guessing version at /path')],
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
