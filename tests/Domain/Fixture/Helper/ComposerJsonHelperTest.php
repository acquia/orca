<?php

namespace Acquia\Orca\Tests\Domain\Fixture\Helper;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaFixtureNotExistsException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use Acquia\Orca\Options\FixtureOptionsFactory;
use Acquia\Orca\Tests\TestCase;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Options\FixtureOptionsFactory|\Prophecy\Prophecy\ObjectProphecy $fixtureOptionsFactory
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
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionResolver::class);
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

  private function createComposerJsonHelperWithConfigSpy(): ComposerJsonHelper {
    $config_loader = $this->configLoader->reveal();
    $fixture = $this->fixture->reveal();
    $fixture_options_factory = $this->fixtureOptionsFactory->reveal();
    return new class($config_loader, $fixture, $fixture_options_factory) extends ComposerJsonHelper {

      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UndefinedVariable
      private $config;

      protected function writeFile(Config $config): void {
        $this->config = $config;
      }

      public function getConfig(): Config {
        return $this->config;
      }

    };
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

  public function testAddAllowedComposerPlugins(): void {
    $config = $this->prophesize(Config::class);
    $config->set("config.allow-plugins.test/example", TRUE)
      ->shouldBeCalledOnce();
    $config->set("config.allow-plugins.lorem/ipsum", TRUE)
      ->shouldBeCalledOnce();
    $config->set("config.allow-plugins.acquia/blt", TRUE)
      ->shouldBeCalledOnce();
    $config->toFile(self::FILENAME)
      ->shouldBeCalledOnce();
    $config = $config->reveal();
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);

    $composer_json = $this->createComposerJsonHelper();
    $composer_json->addAllowedComposerPlugins(["test/example", "lorem/ipsum", "acquia/blt"]);
  }

  public function testAddRepository(): void {
    $config = new Config($this->getTestComposerJsonRaw(), new Json(), TRUE);
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);
    $composer_json = $this->createComposerJsonHelperWithConfigSpy();

    $type = 'path';
    $url = '/var/www/example';
    $composer_json->addRepository('drupal/example_one', $type, $url);
    $composer_json->addRepository('drupal/example_two', $type, $url);

    $expected = [
      'drupal/example_two' => [
        'type' => $type,
        'url' => $url,
        'canonical' => TRUE,
      ],
      'drupal/example_one' => [
        'type' => $type,
        'url' => $url,
        'canonical' => TRUE,
      ],
      'drupal' => [
        'type' => 'composer',
        'url' => 'https://packages.drupal.org/8',
      ],
    ];
    /** @var \Noodlehaus\Config $config */
    $config = $composer_json->getConfig();
    $actual = $config->get('repositories');
    self::assertSame($expected, $actual, 'Correctly added repositories');
  }

  public function testAddInstallerPath(): void {
    $config = new Config($this->getTestComposerJsonRaw(), new Json(), TRUE);
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);
    $composer_json = $this->createComposerJsonHelperWithConfigSpy();

    $matches = [
      "drupal/example_one",
      "drupal/example_two",
    ];
    $composer_json->addInstallerPath('files-private/{$name}', $matches);

    $expected = [
      'files-private/{$name}' => [
        "drupal/example_one",
        "drupal/example_two",
      ],
      'docroot/core' => [
        'type:drupal-core',
      ],
      'docroot/libraries/{$name}' => [
        'type:drupal-library',
        'type:bower-asset',
        'type:npm-asset',
      ],
      'docroot/modules/contrib/{$name}' => [
        'type:drupal-module',
      ],
      'docroot/profiles/contrib/{$name}' => [
        'type:drupal-profile',
      ],
      'docroot/themes/contrib/{$name}' => [
        'type:drupal-theme',
      ],
      'drush/Commands/contrib/{$name}' => [
        'type:drupal-drush',
      ],
      'docroot/modules/custom/{$name}' => [
        'type:drupal-custom-module',
      ],
      'docroot/themes/custom/{$name}' => [
        'type:drupal-custom-theme',
      ],
    ];
    /** @var \Noodlehaus\Config $config */
    $config = $composer_json->getConfig();
    $actual = $config->get('extra.installer-paths');
    self::assertSame($expected, $actual, 'Correctly added repositories');
  }

  public function testAddInstallerPathEmpty(): void {
    $this->configLoader
      ->load(self::FILENAME)
      ->shouldNotBeCalled();
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->addInstallerPath('files-private/{$name}', []);
  }

  public function testSetPreferInstallFromSource(): void {
    $config = new Config($this->getTestComposerJsonRaw(), new Json(), TRUE);
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);
    $composer_json = $this->createComposerJsonHelperWithConfigSpy();

    $packages = [
      'drupal/example_one',
      'drupal/example_two',
    ];
    $composer_json->setPreferInstallFromSource($packages);

    $expected = [
      'drupal/example_one' => 'source',
      'drupal/example_two' => 'source',
    ];
    /** @var \Noodlehaus\Config $config */
    $config = $composer_json->getConfig();
    $actual = $config->get('config.preferred-install');
    self::assertSame($expected, $actual, 'Correctly set preferred install values.');
  }

  public function testSetPreferInstallFromSourceEmpty(): void {
    $this->configLoader
      ->load(self::FILENAME)
      ->shouldNotBeCalled();
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->setPreferInstallFromSource([]);
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
    $this->expectException(OrcaFixtureNotExistsException::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testGetNoComposerJson(): void {
    $this->fixture
      ->exists(self::FILENAME)
      ->willReturn(FALSE);
    $this->expectException(OrcaFileNotFoundException::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testGetComposerJsonMissingFixtureOptions(): void {
    $config = new Config($this->getTestComposerJsonRaw(), new Json(), TRUE);
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);
    $this->expectException(\LogicException::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  public function testGetInvalidComposerJson(): void {
    $this->configLoader
      ->load(self::FILENAME)
      ->willThrow(OrcaParseError::class);
    $this->expectException(OrcaParseError::class);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->getFixtureOptions();
  }

  /**
   * @dataProvider providerSet
   */
  public function testSet($key, $value): void {
    $config = $this->prophesize(Config::class);
    $config->set($key, $value)
      ->shouldBeCalledOnce();
    $config->toFile(Argument::any())
      ->shouldBeCalledOnce();
    $this->configLoader
      ->load(self::FILENAME)
      ->willReturn($config);
    $composer_json = $this->createComposerJsonHelper();

    $composer_json->set($key, $value);
  }

  public function providerSet(): array {
    return [
      ['lorem.ipsum', TRUE],
      ['dolor.sit.amet', 12345],
      ['consectetur', ['example']],
    ];
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
