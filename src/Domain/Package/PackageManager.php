<?php

namespace Acquia\Orca\Domain\Package;

use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;

/**
 * Provides access to packages specified in config.
 */
class PackageManager {

  /**
   * The packages config alter data, if provided.
   *
   * @var array
   */
  private $alterData = [];

  /**
   * The BLT package.
   *
   * @var \Acquia\Orca\Domain\Package\Package|null
   */
  private $blt;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * All defined packages keyed by package name.
   *
   * @var \Acquia\Orca\Domain\Package\Package[]
   */
  private $packages = [];

  /**
   * The YAML parser.
   *
   * @var \Symfony\Component\Yaml\Parser
   */
  private $parser;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Yaml\Parser $parser
   *   The YAML parser.
   * @param string $packages_config
   *   The path to the packages configuration file relative to the ORCA project
   *   directory.
   * @param string|null $packages_config_alter
   *   The path to an extra packages configuration file relative to the ORCA
   *   project directory whose contents will be merged into the main packages
   *   configuration.
   */
  public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, Parser $parser, string $packages_config, ?string $packages_config_alter) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->parser = $parser;
    $this->initializePackages($fixture_path_handler, $packages_config, $packages_config_alter);
  }

  /**
   * Determines whether a given package exists.
   *
   * @param string $package_name
   *   The package name of the package in question, e.g., "drupal/example".
   *
   * @return bool
   *   TRUE if the given package exists or FALSE if not.
   */
  public function exists(string $package_name): bool {
    return array_key_exists($package_name, $this->packages);
  }

  /**
   * Gets a package by package name.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return \Acquia\Orca\Domain\Package\Package
   *   The requested package.
   *
   * @throws \InvalidArgumentException
   *   If the requested package isn't found.
   */
  public function get(string $package_name): Package {
    if (empty($this->packages[$package_name])) {
      throw new \InvalidArgumentException(sprintf('No such package: %s', $package_name));
    }
    return $this->packages[$package_name];
  }

  /**
   * Gets an array of all packages.
   *
   * @return \Acquia\Orca\Domain\Package\Package[]
   *   An array of packages keyed by package name.
   */
  public function getAll(): array {
    return $this->packages;
  }

  /**
   * Gets the BLT package.
   *
   * BLT is a special case due to its foundational relationship to the fixture.
   * It must always be available by direct request, even if absent from the
   * active packages specification.
   *
   * @return \Acquia\Orca\Domain\Package\Package
   *   The BLT package.
   */
  public function getBlt(): Package {
    if (!$this->blt) {
      $this->initializeBlt();
    }
    return $this->blt;
  }

  /**
   * Gets the packages config alter data.
   *
   * @return array
   *   An array of data keyed by package name.
   */
  public function getAlterData(): array {
    return $this->alterData;
  }

  /**
   * Initializes the packages.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param string $packages_config
   *   The path to the packages configuration file relative to the ORCA project
   *   directory.
   * @param string|null $packages_config_alter
   *   The path to an extra packages configuration file relative to the ORCA
   *   project directory whose contents will be merged into the main packages
   *   configuration.
   */
  private function initializePackages(FixturePathHandler $fixture_path_handler, string $packages_config, ?string $packages_config_alter): void {
    $data = $this->parseYamlFile($this->orca->getPath($packages_config));
    if ($packages_config_alter) {
      // Check if given an absolute path, else assume relative path.
      if (file_exists($packages_config_alter)) {
        $alter_path = $packages_config_alter;
      }
      else {
        $alter_path = $this->orca->getPath($packages_config_alter);
      }
      $this->alterData = $this->parseYamlFile($alter_path);
      $data = array_merge($data, $this->alterData);
    }
    foreach ($data as $package_name => $datum) {
      // Skipping a null datum provides for a package to be effectively removed
      // from the active specification at runtime by setting its value to NULL
      // in the packages configuration alter file.
      if ($datum === NULL) {
        continue;
      }

      $package = new Package($datum, $fixture_path_handler, $this->orca, $package_name);
      $this->packages[$package_name] = $package;
    }
    ksort($this->packages);
  }

  /**
   * Parses a given YAML file and returns the data.
   *
   * @param string $file
   *   The file to parse.
   *
   * @return array
   *   The parsed data.
   */
  private function parseYamlFile(string $file): array {
    if (!$this->filesystem->exists($file)) {
      throw new \LogicException("No such file: {$file}");
    }
    $data = $this->parser->parseFile($file);
    if (!is_array($data)) {
      throw new \LogicException("Incorrect schema in {$file}. See config/packages.yml.");
    }
    return $data;
  }

  /**
   * Initializes BLT.
   */
  private function initializeBlt(): void {
    $package_name = 'acquia/blt';

    // If it's in the active packages specification, use it.
    if ($this->exists($package_name)) {
      $this->blt = $this->get($package_name);
      return;
    }

    // Otherwise get it from the default specification.
    $default_packages_yaml = $this->orca->getPath('config/packages.yml');
    $data = $this->parser->parseFile($default_packages_yaml);
    $this->blt = new Package($data[$package_name], $this->fixture, $this->orca, $package_name);
  }

}
