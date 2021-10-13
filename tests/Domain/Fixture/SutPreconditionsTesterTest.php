<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Fixture\SutPreconditionsTester;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Package\Package $package
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @coversDefaultClass \Acquia\Orca\Domain\Fixture\SutPreconditionsTester
 */
class SutPreconditionsTesterTest extends TestCase {

  private const COMPOSER_JSON_DATA = [
    'name' => 'drupal/example',
    'type' => 'drupal-module',
    'description' => 'Provides an example module for testing and illustration purposes.',
    'license' => 'GPL-2.0-or-later',
    'require' => [],
    'extra' => [
      'branch-alias' => [
        'dev-main' => '1.x-dev',
      ],
    ],
    'minimum-stability' => 'dev',
  ];

  private const COMPOSER_JSON_PATH = '/var/www/example/composer.json';

  private const SUT_NAME = 'drupal/example';

  private const SUT_PATH_RAW = '../example';

  private const SUT_PATH_ABSOLUTE = '/var/www/example';

  protected function setUp(): void {
    $config = $this->createConfig(self::COMPOSER_JSON_DATA);
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($config);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->exists(Argument::any())
      ->willReturn(TRUE);
    $this->fixture
      ->getPath(self::SUT_PATH_RAW)
      ->willReturn(self::SUT_PATH_ABSOLUTE);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->package = $this->createPackage([], self::SUT_NAME);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->get(self::SUT_NAME)
      ->willReturn($this->package);
  }

  protected function createSutPreconditionsTester(): SutPreconditionsTester {
    $config_loader = $this->configLoader->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    $package_manager = $this->packageManager->reveal();
    return new SutPreconditionsTester($config_loader, $fixture_path_handler, $package_manager);
  }

  private function createPackage($data, $package_name): Package {
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new Package($data, $fixture_path_handler, $orca_path_handler, $package_name);
  }

  private function createConfig(array $data): Config {
    $values = json_encode($data);
    return new Config($values, new Json(), TRUE);
  }

  public function testTestHappyPath(): void {
    $this->fixture
      ->exists(self::SUT_PATH_ABSOLUTE)
      ->willReturn(TRUE);
    $this->configLoader
      ->load(Argument::any())
      ->shouldBeCalledOnce();

    $tester = $this->createSutPreconditionsTester();

    $tester->test(self::SUT_NAME);
  }

  /**
   * @dataProvider providerTestExceptions
   */
  public function testTestExceptions($caught, $thrown): void {
    $this->configLoader
      ->load(Argument::any())
      ->willThrow($caught);
    $tester = $this->createSutPreconditionsTester();
    $this->expectException($thrown);

    $tester->test(self::SUT_NAME);
  }

  public function providerTestExceptions(): array {
    return [
      [OrcaFileNotFoundException::class, OrcaFileNotFoundException::class],
      [OrcaException::class, OrcaException::class],
      [\Exception::class, \RuntimeException::class],
    ];
  }

  public function testTestMissingComposerJson(): void {
    $this->configLoader
      ->load(Argument::any())
      ->shouldBeCalledOnce()
      ->willThrow(OrcaFileNotFoundException::class);
    $this->expectException(OrcaFileNotFoundException::class);
    $this->expectExceptionMessageMatches('/SUT is missing root composer.json.*/');

    $tester = $this->createSutPreconditionsTester();

    $tester->test(self::SUT_NAME);
  }

  public function testTestComposerJsonNameMismatch(): void {
    $data = self::COMPOSER_JSON_DATA;
    $data['name'] = 'drupal/mismatch';
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($this->createConfig($data));
    $message = "SUT composer.json's 'name' value 'drupal/mismatch' does not match expected 'drupal/example'";
    $this->expectExceptionObject(new OrcaException($message));

    $tester = $this->createSutPreconditionsTester();

    $tester->test(self::SUT_NAME);
  }

  public function testTestComposerVersionSpecified(): void {
    $data = self::COMPOSER_JSON_DATA;
    $data['version'] = 'v1.0.0';
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($this->createConfig($data));
    $this->expectExceptionObject(new OrcaException(implode(PHP_EOL, [
      "SUT composer.json must not specify a 'version'",
      'See https://getcomposer.org/doc/04-schema.md#version',
    ])));

    $tester = $this->createSutPreconditionsTester();

    $tester->test(self::SUT_NAME);
  }

}
