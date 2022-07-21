<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Provide access to company Drupal subextensions physically in the fixture.
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
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The top-level Acquia extensions.
   *
   * @var \Acquia\Orca\Domain\Package\Package[]
   */
  private $topLevelExtensions;

  /**
   * The subextensions found in the fixture.
   *
   * @var \Acquia\Orca\Domain\Package\Package[]
   */
  private $subextensions = [];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(ConfigLoader $config_loader, Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, PackageManager $package_manager) {
    $this->configLoader = $config_loader;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->alterData = $package_manager->getAlterData();
    $this->initializeTopLevelExtensions($package_manager);
    $this->orca = $orca_path_handler;
  }

  /**
   * Initializes the top level extensions.
   *
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function initializeTopLevelExtensions(PackageManager $package_manager): void {
    foreach ($package_manager->getAll() as $package_name => $package) {
      if (!$package->isDrupalExtension()) {
        continue;
      }
      $this->topLevelExtensions[$package_name] = $package;
    }
  }

  /**
   * Gets an array of all company subextensions.
   *
   * @return \Acquia\Orca\Domain\Package\Package[]
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
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package to search for subextensions.
   *
   * @return \Acquia\Orca\Domain\Package\Package[]
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

      // Skip subextension if it has been removed with a NULL value in our
      // alter data, for example, "drupal/example_subextension: ~".
      if (array_key_exists($name, $this->alterData) && $this->alterData[$name] === NULL) {
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

      // Check if any subextension is disabled in packages.yml,
      // for example, "drupal/example_subextension.enable: false".
      if (array_key_exists($name, $this->topLevelExtensions)) {
        $package_subextension = $this->topLevelExtensions[$name];
        $package_data['enable'] = $package_subextension->shouldGetEnabled();
      }

      // Checking for any alterations of a subextension mentioned in
      // packages_alter.yml.
      if (isset($this->alterData[$name])) {
        $alter_data = array_intersect_key($this->alterData[$name], $package_data);
        /* @noinspection SlowArrayOperationsInLoopInspection */
        $package_data = array_replace($package_data, $alter_data);
      }

      $subextensions[$name] = new Package($package_data, $this->fixture, $this->orca, $name);
    }

    return $subextensions;
  }

  /**
   * Finds the dev-dependencies of a given package.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package to search for dev-dependencies.
   *
   * @return array
   *   An indexed array of all the dev-dependencies.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function findDevDependenciesByPackage(Package $package): array {
    $parent_path = $package->getInstallPathAbsolute();
    try {
      $config = $this->configLoader->load("$parent_path/composer.json");
      $require_dev = $config->get("require-dev");
      return $require_dev ?? [];
    }
    catch (OrcaFileNotFoundException $e) {
      throw new OrcaFileNotFoundException("No such file: {$parent_path}/composer.json}");
    }
  }

  /**
   * Finds the plugins configured in allow-plugins config of a given package.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package to search for plugins.
   *
   * @return array
   *   An indexed array of all the dev-dependencies.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function findAllowPluginsByPackage(Package $package): array {
    $parent_path = $package->getInstallPathAbsolute();
    try {
      $config = $this->configLoader->load("$parent_path/composer.json");
      $allow_plugins = $config->get("config.allow-plugins");
      return $allow_plugins ? array_keys($allow_plugins) : [];
    }
    catch (OrcaFileNotFoundException $e) {
      throw new OrcaFileNotFoundException("No such file: {$parent_path}/composer.json}");
    }
  }

  /**
   * Finds all company Drupal subextension composer.json files.
   *
   * @param string $path
   *   A path to recursively search for subextensions.
   *
   * @return \Symfony\Component\Finder\Finder|array
   *   A Finder query for all company Drupal subextension composer.json files
   *   within the given paths or an empty array if no paths are given.
   */
  private function findSubextensionComposerJsonFiles(string $path) {
    if (!$this->filesystem->exists($path)) {
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
      [$vendor_name, $package_name] = explode('/', $name);
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

    // Project names may include a namespace.
    // @see https://www.drupal.org/project/project_composer/issues/3064900
    $name_parts = explode('-', $package_name);
    $extension_name = end($name_parts);

    // Ignore extensions without a corresponding .info.yml file.
    $info_yml_file = "{$file->getPath()}/{$extension_name}.info.yml";
    if (!$this->filesystem->exists($info_yml_file)) {
      return FALSE;
    }

    return TRUE;
  }

}
