<?php

namespace Acquia\Orca\Tests\Domain\Fixture\Helper;

use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Domain\Fixture\FixtureOptionsFactory;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\FileNotFoundException;
use Acquia\Orca\Exception\FixtureNotExistsException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use LogicException;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Fixture\FixtureOptionsFactory|\Prophecy\Prophecy\ObjectProphecy $fixtureOptionsFactory
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @coversDefaultClass \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
 */
class ComposerJsonHelperTest extends TestCase {

  private const CONFIG_KEY = 'extra.orca.options';

  private const FILENAME = 'composer.json';

  private $rawFixtureOptions = [
    'bare' => TRUE,
  ];

  protected function setUp(): void {
    $config = new Config($this->getTestComposerJsonWithFixtureOptionsRaw(), new Json(), TRUE);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($config);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->exists()
      ->willReturn(TRUE);
    $this->fixture
      ->exists(self::FILENAME)
      ->willReturn(TRUE);
    $this->fixture
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->fixtureOptionsFactory = $this->prophesize(FixtureOptionsFactory::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
  }

  private function createComposerJsonHelper(): ComposerJsonHelper {
    $config_loader = $this->configLoader->reveal();
    $fixture = $this->fixture->reveal();
    $fixture_options_factory = $this->fixtureOptionsFactory->reveal();
    return new ComposerJsonHelper($config_loader, $fixture, $fixture_options_factory);
  }

  private function createFixtureOptions($options): FixtureOptions {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new FixtureOptions($drupal_core_version_finder, $package_manager, $options);
  }

  private function getTestComposerJsonRaw(): string {
    return file_get_contents(__DIR__ . '/' . self::FILENAME);
  }

  private function getTestComposerJsonData(): array {
    return json_decode($this->getTestComposerJsonRaw(), TRUE);
  }

  private function getTestComposerJsonDataWithFixtureOptions(): array {
    $data = $this->getTestComposerJsonData();
    $data['extra']['orca']['options'] = $this->rawFixtureOptions;
    return $data;
  }

  private function getTestComposerJsonWithFixtureOptionsRaw(): string {
    return json_encode($this->getTestComposerJsonDataWithFixtureOptions());
  }

  public function testGetFixtureOptions(): void {
    $config = new Config($this->getTestComposerJsonWithFixtureOptionsRaw(), new Json(), TRUE);
    $this->configLoader
      ->load(self::FILENAME)
      ->shouldBeCalledOnce()
      ->willReturn($config);
    $provided_options = $this->createFixtureOptions($this->rawFixtureOptions);
    $this->fixtureOptionsFactory
      ->create($this->rawFixtureOptions)
      ->willReturn($provided_options);
    $composer_json = $this->createComposerJsonHelper();

    // Call once to test essential functionality.
    $retrieved_options1 = $composer_json->getFixtureOptions();
    // Call again to test value caching.
    $composer_json->getFixtureOptions();

    self::assertEquals($provided_options, $retrieved_options1);
  }

  public function testGetNoFixture(): void {
    $this->fixture
      ->exists()
      ->willReturn(FALSE);
    $this->expectException(FixtureNotExistsException::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testGetNoComposerJson(): void {
    $this->fixture
      ->exists(self::FILENAME)
      ->willReturn(FALSE);
    $this->expectException(FileNotFoundException::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testGetComposerJsonMissingFixtureOptions(): void {
    $config = new Config($this->getTestComposerJsonRaw(), new Json(), TRUE);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);
    $this->expectException(LogicException::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testGetInvalidComposerJson(): void {
    $this->configLoader
      ->load(self::FILENAME)
      ->willThrow(ParseError::class);
    $this->expectException(ParseError::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testWriteOptions(): void {
    $config = $this->prophesize(Config::class);
    $config->set(self::CONFIG_KEY, $this->rawFixtureOptions)
      ->shouldBeCalledOnce();
    $config->toFile(self::FILENAME)
      ->shouldBeCalledOnce();
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config->reveal());
    $options = $this->createFixtureOptions($this->rawFixtureOptions);
    $this->fixtureOptionsFactory
      ->create($this->rawFixtureOptions)
      ->willReturn($options);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->writeFixtureOptions($options);
  }

}
