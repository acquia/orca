<?php

namespace Acquia\Orca\Fixture;

use Symfony\Component\Yaml\Yaml;

/**
 * Provides access to Acquia product module data.
 */
class ProductData {

  /**
   * Product module data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Constructs an instance.
   *
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(string $project_dir) {
    $yaml = file_get_contents("{$project_dir}/config/projects.yml");
    $this->data = Yaml::parse($yaml);
  }

  /**
   * Returns the directory name for the given package.
   *
   * @param string $package
   *   A package name, e.g., drupal/example.
   *
   * @return string
   */
  public function dir($package) {
    if (!array_key_exists($package, $this->data)) {
      throw new \InvalidArgumentException(
        sprintf('No such package: "%s"', $package)
      );
    }

    return $this->data[$package]['dir'];
  }

  /**
   * Determines whether or not the given package is a valid, Acquia product.
   *
   * @param string $package
   *   A package name, e.g., drupal/example.
   *
   * @return bool
   */
  public function isValidPackage(string $package) {
    return in_array($package, $this->packageNames());
  }

  /**
   * Gets the main module name for a given package.
   *
   * @param string $package
   *   A package name, e.g., drupal/example.
   *
   * @return string|false
   *   A module name, if available, or FALSE if not.
   */
  public function moduleName($package) {
    if (empty($this->data[$package]['module'])) {
      return FALSE;
    }

    return $this->data[$package]['module'];
  }

  /**
   * Returns an array of Drupal module names, optionally limited by package.
   *
   * @param string|null $package
   *   (Optional) A package name to limit to, or NULL for all.
   *
   * @return string[]
   */
  public function moduleNamePlural(?string $package = NULL) {
    $modules = [];
    foreach ($this->data as $package_name => $data) {
      if ($package && $package !== $package_name) {
        continue;
      }

      if (!empty($data['module'])) {
        $modules[] = $data['module'];
        if (!empty($data['submodules'])) {
          foreach ($data['submodules'] as $submodule) {
            $modules[] = $submodule;
          }
        }
      }
    }
    return $modules;
  }

  /**
   * Returns an array of Composer package names.
   *
   * @return string[]
   */
  public function packageNames() {
    return array_keys($this->data);
  }

  /**
   * Returns an array of Composer package strings, including names and versions.
   *
   * @return string[]
   */
  public function packageStringPlural() {
    $packages = [];
    foreach ($this->data as $package_name => $datum) {
      if (!empty($datum['version'])) {
        $packages[$package_name] = "{$package_name}:{$datum['version']}";
        if (!empty($datum['submodules'])) {
          foreach ($datum['submodules'] as $submodule) {
            $packages["drupal/{$submodule}"] = "drupal/{$submodule}:{$datum['version']}";
          }
        }
      }
    }
    return $packages;
  }

  /**
   * Returns a Composer project name for a given package.
   *
   * That is, the part of the package strings after the forward slash (/).
   *
   * @param string $package
   *   (Optional) A package name to limit to, or NULL for all.
   *
   * @return bool|string
   */
  public function projectName(string $package): string {
    return substr($package, strpos($package, '/') + 1);
  }

  /**
   * Returns an array of Composer project names.
   *
   * That is, the part of the package strings after the forward slash (/).
   *
   * @param string|null $package
   *   (Optional) A package name to limit to, or NULL for all.
   *
   * @return string[]
   */
  public function projectNamePlural(?string $package = NULL) {
    $names = [];
    $data = ($package) ? [$package => []] : $this->data;
    foreach (array_keys($data) as $package_name) {
      $names[] = $this->projectName($package_name);
    }
    return $names;
  }

}
