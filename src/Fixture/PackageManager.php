<?php

namespace Acquia\Orca\Fixture;

use Symfony\Component\Yaml\Parser;

/**
 * Provides access to packages specified in config.
 */
class PackageManager {

  /**
   * All defined packages keyed by package name.
   *
   * @var \Acquia\Orca\Fixture\Package[]
   */
  private $packages = [];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Yaml\Parser $parser
   *   The YAML parser.
   * @param string $packages_config
   *   The path to the packages configuration file relative to the ORCA project
   *   directory.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Fixture $fixture, Parser $parser, string $packages_config, string $project_dir) {
    $data = $parser->parseFile("{$project_dir}/{$packages_config}");
    foreach ($data as $datum) {
      $package = new Package($fixture, $datum);
      $this->packages[$package->getPackageName()] = $package;
    }
  }

  /**
   * Determines whether a given package exists.
   *
   * @param string $package_name
   *   The package name of the package in question, e.g., "drupal/example".
   *
   * @return bool
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
   * @param string|null $getter
   *   (Optional) A getter method to call on the objects to get the return array
   *   values, or NULL to return the full objects. Defaults to NULL.
   *
   * @return \Acquia\Orca\Fixture\Package[]|string[]
   *   An array of packages or package properties keyed by package name.
   */
  public function getMultiple(?string $type = NULL, ?string $getter = NULL): array {
    $packages = [];
    foreach ($this->packages as $package_name => $package) {
      if ($type && $package->getType() !== $type) {
        continue;
      }

      if ($getter && method_exists($package, $getter)) {
        $packages[$package_name] = $package->{$getter}();
        continue;
      }

      $packages[$package_name] = $package;
    }
    return $packages;
  }

}
