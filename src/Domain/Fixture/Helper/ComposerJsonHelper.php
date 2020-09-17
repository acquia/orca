<?php

namespace Acquia\Orca\Domain\Fixture\Helper;

use Acquia\Orca\Domain\Fixture\FixtureOptions;
use Acquia\Orca\Domain\Fixture\FixtureOptionsFactory;
use Acquia\Orca\Exception\FileNotFoundException;
use Acquia\Orca\Exception\FixtureNotExistsException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use LogicException;
use Noodlehaus\Config;

/**
 * Provides facilities for working with the fixture's composer.json.
 */
class ComposerJsonHelper {

  private const CONFIG_KEY = 'extra.orca.options';

  private const FILENAME = 'composer.json';

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture options, if available.
   *
   * @var \Acquia\Orca\Domain\Fixture\FixtureOptions|null
   */
  private $fixtureOptions;

  /**
   * The fixture options factory.
   *
   * @var \Acquia\Orca\Domain\Fixture\FixtureOptionsFactory
   */
  private $fixtureOptionsFactory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Fixture\FixtureOptionsFactory $fixture_options_factory
   *   The fixture options factory.
   */
  public function __construct(ConfigLoader $config_loader, FixturePathHandler $fixture_path_handler, FixtureOptionsFactory $fixture_options_factory) {
    $this->configLoader = $config_loader;
    $this->fixture = $fixture_path_handler;
    $this->fixtureOptionsFactory = $fixture_options_factory;
  }

  /**
   * Gets the fixture options.
   *
   * @return \Acquia\Orca\Domain\Fixture\FixtureOptions
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\FixtureNotExistsException
   * @throws \Acquia\Orca\Exception\InvalidArgumentException
   * @throws \Acquia\Orca\Exception\ParseError
   */
  public function getFixtureOptions(): FixtureOptions {
    if ($this->fixtureOptions) {
      return $this->fixtureOptions;
    }

    $config = $this->loadFile();
    $raw_options = $config->get(self::CONFIG_KEY);

    if (!$raw_options) {
      throw new LogicException('Fixture composer.json is missing fixture options data.');
    }

    $this->fixtureOptions = $this->fixtureOptionsFactory
      ->create($raw_options);
    return $this->fixtureOptions;
  }

  /**
   * Loads the file.
   *
   * @return \Noodlehaus\Config
   *   The file as a config object.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\FixtureNotExistsException
   * @throws \Acquia\Orca\Exception\ParseError
   */
  private function loadFile(): Config {
    if (!$this->fixture->exists()) {
      throw new FixtureNotExistsException('No fixture exists.');
    }
    if (!$this->fixture->exists(self::FILENAME)) {
      throw new FileNotFoundException('Fixture is missing composer.json.');
    }

    try {
      $config = $this->configLoader->load($this->filePath());
    }
    catch (ParseError $e) {
      throw new ParseError('Fixture composer.json is corrupted.');
    }
    return $config;
  }

  /**
   * Write the fixture options to the file.
   *
   * @param \Acquia\Orca\Domain\Fixture\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\FixtureNotExistsException
   * @throws \Acquia\Orca\Exception\ParseError
   */
  public function writeFixtureOptions(FixtureOptions $options): void {
    $config = $this->loadFile();
    $config->set(self::CONFIG_KEY, $options->getRawOptions());
    $config->toFile($this->filePath());
  }

  /**
   * Gets the file path.
   *
   * @return string
   *   The file path.
   */
  private function filePath(): string {
    return $this->fixture->getPath(self::FILENAME);
  }

}
