<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
use Noodlehaus\Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Provide access to Acquia Drupal submodules physically in the fixture.
 */
class SubmoduleManager {

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Utility\ConfigLoader
   */
  private $configLoader;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The top-level Acquia modules.
   *
   * @var \Acquia\Orca\Fixture\Package[]
   */
  private $topLevelModules;

  /**
   * The submodules found in the fixture.
   *
   * @var \Acquia\Orca\Fixture\Package[]
   */
  private $submodules = [];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigLoader $config_loader
   *   The config loader.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(ConfigLoader $config_loader, Filesystem $filesystem, Fixture $fixture, PackageManager $package_manager) {
    $this->configLoader = $config_loader;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture;
    $this->topLevelModules = $package_manager->getMultiple('drupal-module');
  }

  /**
   * Gets an array of all Acquia submodules.
   *
   * @return \Acquia\Orca\Fixture\Package[]
   *   An indexed array of package objects.
   */
  public function getAll(): array {
    if ($this->submodules) {
      return $this->submodules;
    }
    $paths = $this->getTopLevelModuleInstallPaths();
    $this->submodules = $this->getInPaths($paths);
    return $this->submodules;
  }

  /**
   * Gets an array of submodules of a given parent.
   *
   * @param \Acquia\Orca\Fixture\Package $package
   *   The package to search for submodules.
   *
   * @return \Acquia\Orca\Fixture\Package[]
   *   An indexed array of package objects.
   */
  public function getByParent(Package $package): array {
    $paths = [$package->getInstallPathAbsolute()];
    return $this->getInPaths($paths);
  }

  /**
   * Gets an array of submodules in a given set of paths.
   *
   * @param string[] $paths
   *   The paths to search for submodules.
   *
   * @return \Acquia\Orca\Fixture\Package[]
   *   An indexed array of package objects.
   */
  public function getInPaths(array $paths): array {
    $submodules = [];
    foreach ($this->findSubmoduleComposerJsonFiles($paths) as $file) {
      try {
        $config = $this->configLoader->load($file->getPathname());
      }
      catch (\Exception $e) {
        continue;
      }
      $name = $config->get('name');
      $install_path = str_replace("{$this->fixture->getPath()}/", '', $file->getPath());
      $package_data = [
        'install_path' => $install_path,
        'url' => $file->getPath(),
        'version' => '@dev',
        'version_dev' => '@dev',
        'enable' => $this->shouldModuleGetEnabled($config, $install_path),
      ];
      $submodules[$name] = new Package($this->fixture, $name, $package_data);
    }
    return $submodules;
  }

  /**
   * Determines whether or not the given submodule should get enabled.
   *
   * Test modules are never enabled because Drush cannot find them to enable.
   * Standard modules are enabled unless they opt out by setting
   * extra.orca.enable to FALSE in their composer.json.
   *
   * @param \Noodlehaus\Config $config
   *   The submodule's composer.json config.
   * @param string $install_path
   *   The path the module installs at.
   *
   * @return bool
   *   TRUE if the submodule should be enabled or FALSE if not.
   */
  private function shouldModuleGetEnabled(Config $config, $install_path): bool {
    $is_test_module = (strpos($install_path, '/tests/') !== FALSE);
    if ($is_test_module) {
      return FALSE;
    }

    return $config->get('extra.orca.enable', TRUE);
  }

  /**
   * Gets an array of top level module install paths.
   *
   * @return string[]
   *   An indexed array of paths.
   */
  private function getTopLevelModuleInstallPaths(): array {
    $paths = [];
    foreach ($this->topLevelModules as $package) {
      $path = $package->getInstallPathAbsolute();
      if ($this->filesystem->exists($path)) {
        $paths[] = $path;
      }
    }
    return $paths;
  }

  /**
   * Finds all Acquia Drupal submodule composer.json files.
   *
   * @param string[] $paths
   *   An array of paths to recursively search for submodules.
   *
   * @return \Symfony\Component\Finder\Finder|array
   *   A Finder query for all Acquia Drupal submodule composer.json files within
   *   the given paths or an empty array if no paths are given.
   */
  private function findSubmoduleComposerJsonFiles(array $paths) {
    if (!$paths) {
      return [];
    }
    return (new Finder())
      ->files()
      ->followLinks()
      ->in($paths)
      ->depth('> 1')
      ->exclude([
        // Ignore package vendor directories. (These should never exist on CI.
        // This is mostly for local development.)
        'docroot',
        'vendor',
      ])
      ->name('composer.json')
      ->filter(function (\SplFileInfo $file) {
        return $this->isSubmoduleComposerJson($file);
      });
  }

  /**
   * Determines whether a given composer.json file belongs to a submodule.
   *
   * @param \SplFileInfo $file
   *   The file to examine.
   *
   * @return bool
   *   TRUE if the given composer.json file belongs to a submodule or FALSE if
   *   not.
   */
  private function isSubmoduleComposerJson(\SplFileInfo $file): bool {
    try {
      $config = $this->configLoader->load($file->getPathname());
      $name = $config->get('name');
      if (!$name || strpos($name, '/') === FALSE) {
        return FALSE;
      }
      list($vendor_name, $package_name) = explode('/', $name);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    // Ignore everything but Drupal modules.
    if ($config->get('type') !== 'drupal-module') {
      return FALSE;
    }

    // Ignore modules that aren't under the "drupal" vendor name.
    if ($vendor_name !== 'drupal') {
      return FALSE;
    }

    // Ignore modules without a corresponding .info.yml file.
    $info_yml_file = "{$file->getPath()}/{$package_name}.info.yml";
    if (!$this->filesystem->exists($info_yml_file)) {
      return FALSE;
    }

    return TRUE;
  }

}
