<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Provide access to Acquia Drupal subextensions physically in the fixture.
 */
class SubextensionManager {

  /**
   * The active packages config alter data.
   *
   * @var array
   */
  private $alterData = [];

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
   * The top-level Acquia extensions.
   *
   * @var \Acquia\Orca\Fixture\Package[]
   */
  private $topLevelExtensions;

  /**
   * The subextensions found in the fixture.
   *
   * @var \Acquia\Orca\Fixture\Package[]
   */
  private $subextensions = [];

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
    $this->alterData = $package_manager->getAlterData();
    $this->topLevelExtensions = array_merge(
      $package_manager->getMultiple('drupal-module'),
      $package_manager->getMultiple('drupal-theme')
    );
  }

  /**
   * Gets an array of all Acquia subextensions.
   *
   * @return \Acquia\Orca\Fixture\Package[]
   *   An indexed array of package objects.
   */
  public function getAll(): array {
    if ($this->subextensions) {
      return $this->subextensions;
    }
    foreach ($this->topLevelExtensions as $package) {
      $this->subextensions += $this->getByParent($package);
    }
    return $this->subextensions;
  }

  /**
   * Gets an array of subextensions of a given parent.
   *
   * @param \Acquia\Orca\Fixture\Package $package
   *   The package to search for subextensions.
   *
   * @return \Acquia\Orca\Fixture\Package[]
   *   An indexed array of package objects.
   */
  public function getByParent(Package $package): array {
    $subextensions = [];

    $parent_path = $package->getInstallPathAbsolute();
    foreach ($this->findSubextensionComposerJsonFiles($parent_path) as $file) {
      try {
        $config = $this->configLoader->load($file->getPathname());
      }
      catch (\Exception $e) {
        continue;
      }

      $name = $config->get('name');

      if (array_key_exists($name, $this->alterData) && is_null($this->alterData[$name])) {
        continue;
      }

      $install_path = str_replace("{$this->fixture->getPath()}/", '', $file->getPath());
      $package_data = [
        'type' => $config->get('type'),
        'install_path' => $install_path,
        'url' => $file->getPath(),
        'version' => $package->getVersionRecommended(),
        'version_dev' => $package->getVersionDev(),
        // Discovered extensions are enabled unless they opt out by setting
        // extra.orca.enable to FALSE in their composer.json.
        'enable' => $config->get('extra.orca.enable', TRUE),
      ];

      if (isset($this->alterData[$name])) {
        $alter_data = array_intersect_key($this->alterData[$name], $package_data);
        $package_data = array_replace($package_data, $alter_data);
      }

      $subextensions[$name] = new Package($this->fixture, $name, $package_data);
    }

    return $subextensions;
  }

  /**
   * Finds all Acquia Drupal subextension composer.json files.
   *
   * @param string $path
   *   A path to recursively search for subextensions.
   *
   * @return \Symfony\Component\Finder\Finder|array
   *   A Finder query for all Acquia Drupal subextension composer.json files
   *   within the given paths or an empty array if no paths are given.
   */
  private function findSubextensionComposerJsonFiles(string $path) {
    if (!$path) {
      return [];
    }
    return (new Finder())
      ->files()
      ->followLinks()
      ->in($path)
      ->depth('> 0')
      ->exclude([
        // Test extensions are never enabled because Drush cannot find them to
        // enable.
        'tests',
        // Ignore package vendor directories. (These should never exist on CI.
        // This is mostly for local development.)
        'docroot',
        'vendor',
      ])
      ->name('composer.json')
      ->filter(function (\SplFileInfo $file) {
        return $this->isSubextensionComposerJson($file);
      });
  }

  /**
   * Determines whether a given composer.json file belongs to a subextension.
   *
   * @param \SplFileInfo $file
   *   The file to examine.
   *
   * @return bool
   *   TRUE if the given composer.json file belongs to a subextension or FALSE
   *   if not.
   */
  private function isSubextensionComposerJson(\SplFileInfo $file): bool {
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

    // Ignore everything but Drupal extensions.
    if (!in_array($config->get('type'), ['drupal-module', 'drupal-theme'])) {
      return FALSE;
    }

    // Ignore extensions that aren't under the "drupal" vendor name.
    if ($vendor_name !== 'drupal') {
      return FALSE;
    }

    // Ignore extensions without a corresponding .info.yml file.
    $info_yml_file = "{$file->getPath()}/{$package_name}.info.yml";
    if (!$this->filesystem->exists($info_yml_file)) {
      return FALSE;
    }

    return TRUE;
  }

}
