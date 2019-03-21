<?php

namespace Acquia\Orca\Fixture;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;

/**
 * Provides access to packages specified in config.
 */
class PackageManager {

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * All defined packages keyed by package name.
   *
   * @var \Acquia\Orca\Fixture\Package[]
   */
  private $packages = [];

  /**
   * The YAML parser.
   *
   * @var \Symfony\Component\Yaml\Parser
   */
  private $parser;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  private $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Yaml\Parser $parser
   *   The YAML parser.
   * @param string $packages_config
   *   The path to the packages configuration file relative to the ORCA project
   *   directory.
   * @param string|null $packages_config_alter
   *   The path to an extra packages configuration file relative to the ORCA
   *   project directory whose contents will be merged into the main packages
   *   configuration.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Filesystem $filesystem, Fixture $fixture, Parser $parser, string $packages_config, ?string $packages_config_alter, string $project_dir) {
    $this->filesystem = $filesystem;
    $this->parser = $parser;
    $this->projectDir = $project_dir;
    $this->initializePackages($fixture, $packages_config, $packages_config_alter);
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
   * @return \Acquia\Orca\Fixture\Package
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
   * Gets an array of packages or package values, optionally filtered by type.
   *
   * @param string|null $type
   *   (Optional) A type to filter to, e.g., "drupal-module", or NULL to not
   *   filter by type. Defaults to NULL.
   *
   * @return \Acquia\Orca\Fixture\Package[]|string[]
   *   An array of packages or package properties keyed by package name.
   */
  public function getMultiple(?string $type = NULL): array {
    $packages = [];
    foreach ($this->packages as $package_name => $package) {
      if ($type && $package->getType() !== $type) {
        continue;
      }

      $packages[$package_name] = $package;
    }
    return $packages;
  }

  /**
   * Initializes the packages.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param string $packages_config
   *   The path to the packages configuration file relative to the ORCA project
   *   directory.
   * @param string|null $packages_config_alter
   *   The path to an extra packages configuration file relative to the ORCA
   *   project directory whose contents will be merged into the main packages
   *   configuration.
   */
  private function initializePackages(Fixture $fixture, string $packages_config, ?string $packages_config_alter): void {
    $data = $this->parseYamlFile("{$this->projectDir}/{$packages_config}");
    if ($packages_config_alter) {
      $data = array_merge($data, $this->parseYamlFile("{$this->projectDir}/{$packages_config_alter}"));
    }
    foreach ($data as $package_name => $datum) {
      // Skipping a falsy datum provides for a package to be effectively removed
      // from the active specification at runtime by setting its value to NULL
      // in the packages configuration alter file.
      if (!$datum) {
        continue;
      }

      $package = new Package($fixture, $package_name, $datum);
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

}
