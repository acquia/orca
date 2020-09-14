<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\VersionGuesser;
use Acquia\Orca\Exception\FileNotFoundException as OrcaFileNotFoundExceptionAlias;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Composer\Package\Version\VersionGuesser as ComposerVersionGuesser;
use Exception;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundExceptionAlias;
use Noodlehaus\Exception\ParseException;
use Noodlehaus\Parser\Json;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Composer\Package\Version\VersionGuesser|\Prophecy\Prophecy\ObjectProphecy $composerGuesser
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\Version\VersionGuesser
 */
class VersionGuesserTest extends TestCase {

  protected function setUp(): void {
    $this->composerGuesser = $this->prophesize(ComposerVersionGuesser::class);
    $this->configLoader = $this->prophesize(ConfigLoader::class);
  }

  private function createComposer(): VersionGuesser {
    $config_loader = $this->configLoader->reveal();
    $composer_version_guesser = $this->composerGuesser->reveal();
    return new VersionGuesser($config_loader, $composer_version_guesser);
  }

  /**
   * @dataProvider providerGuessVersion
   *
   * @covers ::__construct
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
    $this->composerGuesser
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
   * @covers ::__construct
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

  public function providerGuessVersionWithException(): array {
    return [
      [new NoodlehausFileNotFoundExceptionAlias(''), new OrcaFileNotFoundExceptionAlias('No such file: /path/composer.json')],
      [new ParseException(['message' => '']), new ParseError('Cannot parse /path/composer.json')],
      [new Exception(''), new OrcaException('Unknown error guessing version at /path')],
    ];
  }

}
