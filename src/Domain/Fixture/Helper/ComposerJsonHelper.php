<?php

namespace Acquia\Orca\Domain\Fixture\Helper;

use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaFixtureNotExistsException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use Acquia\Orca\Options\FixtureOptionsFactory;
use Noodlehaus\Config;

/**
 * Provides facilities for working with the fixture's composer.json.
 */
class ComposerJsonHelper {

  private const COMPOSER_JSON = 'composer.json';

  private const CONFIG_PREFERRED_INSTALL = 'config.preferred-install';

  private const EXTRA_INSTALLER_PATHS = 'extra.installer-paths';

  private const EXTRA_ORCA_OPTIONS = 'extra.orca.options';

  private const REQUIRE = 'require';

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
   * @var \Acquia\Orca\Options\FixtureOptions|null
   */
  private $fixtureOptions;

  /**
   * The fixture options factory.
   *
   * @var \Acquia\Orca\Options\FixtureOptionsFactory
   */
  private $fixtureOptionsFactory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Options\FixtureOptionsFactory $fixture_options_factory
   *   The fixture options factory.
   */
  public function __construct(ConfigLoader $config_loader, FixturePathHandler $fixture_path_handler, FixtureOptionsFactory $fixture_options_factory) {
    $this->configLoader = $config_loader;
    $this->fixture = $fixture_path_handler;
    $this->fixtureOptionsFactory = $fixture_options_factory;
  }

  /**
   * Writes the "allow-plugins" configurations to composer.
   *
   * @param string[] $values
   *   The packages required to be added to "allow-plugins" config.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function addAllowedComposerPlugins(array $values): void {
    $config = $this->loadFile();
    foreach ($values as $value) {
      $config->set("config.allow-plugins." . $value, TRUE);
    }

    $this->writeFile($config);
  }

  /**
   * Adds an installer path.
   *
   * @param string $path
   *   The path relative to the repository root.
   * @param array $patterns
   *   The patterns to install at the given path.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   *
   * @see https://github.com/composer/installers#custom-install-paths
   */
  public function addInstallerPath(string $path, array $patterns): void {
    if (!$patterns) {
      return;
    }

    $config = $this->loadFile();

    // Installer paths seem to take precedence in the order specified (i.e.,
    // first match found wins), so additions must be PREPENDED to take effect.
    $config->set(self::EXTRA_INSTALLER_PATHS, [
      $path => $patterns,
    ] + $config->get(self::EXTRA_INSTALLER_PATHS, []));

    $this->writeFile($config);
  }

  /**
   * Gets the fixture options.
   *
   * @return \Acquia\Orca\Options\FixtureOptions
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function getFixtureOptions(): FixtureOptions {
    if ($this->fixtureOptions) {
      return $this->fixtureOptions;
    }

    $config = $this->loadFile();
    $raw_options = $config->get(self::EXTRA_ORCA_OPTIONS);

    if (!$raw_options) {
      throw new \LogicException('Fixture composer.json is missing fixture options data.');
    }

    $this->fixtureOptions = $this->fixtureOptionsFactory
      ->create($raw_options);
    return $this->fixtureOptions;
  }

  /**
   * Get packages required by composer.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function getRequiredPackages(): array {
    $config = $this->loadFile();
    return array_keys($config->get(self::REQUIRE));
  }

  /**
   * Configures Composer to install a given list of packages from source.
   *
   * @param string[] $packages
   *   The packages to install from source.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   *
   * @see https://getcomposer.org/doc/06-config.md#preferred-install
   */
  public function setPreferInstallFromSource(array $packages): void {
    if (!$packages) {
      return;
    }

    $config = $this->loadFile();

    // The preferred-install patterns are applied in the order specified, so
    // overrides need to be added to the beginning in order to take effect.
    $value = array_fill_keys($packages, 'source');
    $config->set(self::CONFIG_PREFERRED_INSTALL, $value + $config->get(self::CONFIG_PREFERRED_INSTALL, []));

    $this->writeFile($config);
  }

  /**
   * Adds a Composer repository.
   *
   * @param string $name
   *   The name.
   * @param string $type
   *   The type, e.g., "composer" or "path".
   * @param string $url
   *   A fully qualified URL or a local path.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function addRepository(string $name, string $type, string $url): void {
    $config = $this->loadFile();

    // Repositories take precedence in the order specified (i.e., first match
    // found wins), so additions must be PREPENDED to take effect.
    $key = 'repositories';
    $config->set($key, [
      $name => [
        'type' => $type,
        'url' => $url,
        // Ensure that Composer stops looking for matches once it identifies the
        // path repository.
        // @see https://getcomposer.org/doc/articles/repository-priorities.md#canonical-repositories
        'canonical' => TRUE,
      ],
    ] + $config->get($key, []));

    $this->writeFile($config);
  }

  /**
   * Sets a config value.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function set(string $key, $value): void {
    $config = $this->loadFile();
    $config->set($key, $value);
    $this->writeFile($config);
  }

  /**
   * Write the fixture options to the file.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function writeFixtureOptions(FixtureOptions $options): void {
    $config = $this->loadFile();
    $config->set(self::EXTRA_ORCA_OPTIONS, $options->getRawOptions());
    $this->writeFile($config);
  }

  /**
   * Writes the file.
   *
   * @param \Noodlehaus\Config $config
   *   The file as a config object.
   */
  protected function writeFile(Config $config): void {
    $config->toFile($this->filePath());
  }

  /**
   * Gets the file path.
   *
   * @return string
   *   The file path.
   */
  private function filePath(): string {
    return $this->fixture->getPath(self::COMPOSER_JSON);
  }

  /**
   * Loads the file.
   *
   * @return \Noodlehaus\Config
   *   The file as a config object.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  private function loadFile(): Config {
    if (!$this->fixture->exists()) {
      throw new OrcaFixtureNotExistsException('No fixture exists.');
    }
    if (!$this->fixture->exists(self::COMPOSER_JSON)) {
      throw new OrcaFileNotFoundException('Fixture is missing composer.json.');
    }

    try {
      $config = $this->configLoader->load($this->filePath());
    }
    catch (OrcaParseError $e) {
      throw new OrcaParseError('Fixture composer.json is corrupted.');
    }
    return $config;
  }

}
