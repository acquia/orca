<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Drush\DrushFacade;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Package\Package;
use Acquia\Orca\Package\PackageManager;
use Acquia\Orca\Utility\ConfigLoader;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Installs company Drupal extensions.
 */
class CompanyExtensionEnabler {

  use SutSettingsTrait;

  private const TYPE_MODULE = 'drupal-module';

  private const TYPE_THEME = 'drupal-theme';

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Utility\ConfigLoader
   */
  private $configLoader;

  /**
   * The Drush facade.
   *
   * @var \Acquia\Orca\Drush\DrushFacade
   */
  private $drush;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The bare flag.
   *
   * @var bool
   */
  private $isBare = FALSE;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The subextension manager.
   *
   * @var \Acquia\Orca\Fixture\SubextensionManager
   */
  private $subextensionManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Drush\DrushFacade $drush_facade
   *   The Drush facade.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   */
  public function __construct(ConfigLoader $config_loader, DrushFacade $drush_facade, Filesystem $filesystem, FixturePathHandler $fixture_path_handler, SymfonyStyle $output, PackageManager $package_manager, SubextensionManager $subextension_manager) {
    $this->configLoader = $config_loader;
    $this->drush = $drush_facade;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->output = $output;
    $this->packageManager = $package_manager;
    $this->subextensionManager = $subextension_manager;
  }

  /**
   * Enables extensions.
   *
   * @throws \Exception
   */
  public function enable(): void {
    $this->getFixtureSettings();
    $this->enableAcquiaExtensions();
  }

  /**
   * Gets the fixture settings.
   *
   * @throws \Exception
   */
  private function getFixtureSettings(): void {
    $config = $this->configLoader->load($this->fixture->getPath('composer.json'));
    $this->setSut($config->get('extra.orca.sut'));
    $this->setSutOnly($config->get('extra.orca.is-sut-only', FALSE));
    $this->isBare = $config->get('extra.orca.is-bare', FALSE);
  }

  /**
   * Enables the company extensions.
   */
  private function enableAcquiaExtensions(): void {
    if ($this->isBare) {
      $this->output->warning('No extensions to enable because the fixture is bare');
      return;
    }

    if ($this->isSutOnly && !$this->sut->isDrupalExtension()) {
      $this->output->warning('No extensions to enable because the fixture is SUT-only and the SUT is not a Drupal extension');
      return;
    }

    $this->output->section('Enabling company modules & themes');
    $this->enableModules();
    $this->enableThemes();
  }

  /**
   * Enables the company modules.
   */
  private function enableModules(): void {
    $modules = $this->getCompanyExtensionList(self::TYPE_MODULE);
    if (!$modules) {
      return;
    }
    $this->drush->enableModules($modules);
  }

  /**
   * Enables the company themes.
   */
  private function enableThemes(): void {
    $theme_list = $this->getCompanyExtensionList(self::TYPE_THEME);
    if (!$theme_list) {
      return;
    }
    $this->drush->enableThemes($theme_list);
  }

  /**
   * Gets the list of company extensions to enable.
   *
   * @param string $extension_type
   *   The extension type: ::TYPE_MODULE or ::TYPE_THEME.
   *
   * @return string[]
   *   An indexed array of company extension machine names.
   */
  private function getCompanyExtensionList(string $extension_type): array {
    $extension_list = [];

    $top_level_packages = $this->packageManager->getAll();
    if ($this->isSutOnly) {
      $top_level_packages = [$this->sut];
    }

    foreach ($top_level_packages as $package) {
      if ($this->shouldGetEnabled($package, $extension_type)) {
        $extension_list[] = $package->getProjectName();
      }

      if (!$package->isDrupalExtension()) {
        continue;
      }

      foreach ($this->subextensionManager->getByParent($package) as $subextension) {
        if (!$this->shouldGetEnabled($subextension, $extension_type)) {
          continue;
        }
        $extension_list[] = $subextension->getDrupalExtensionName();
      }
    }

    return $extension_list;
  }

  /**
   * Determines whether or not a given packages should get enabled.
   *
   * @param \Acquia\Orca\Package\Package $package
   *   The package to consider.
   * @param string $extension_type
   *   The type of extension that should get enabled: ::TYPE_MODULE or
   *   ::TYPE_THEME.
   *
   * @return bool
   *   TRUE if the given package should be enabled or FALSE if not.
   */
  private function shouldGetEnabled(Package $package, string $extension_type): bool {
    return $package->getType() === $extension_type
      && $package->shouldGetEnabled()
      && $this->filesystem->exists($package->getInstallPathAbsolute());
  }

}
