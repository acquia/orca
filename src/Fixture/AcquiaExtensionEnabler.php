<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Installs Acquia Drupal extensions.
 */
class AcquiaExtensionEnabler {

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
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The fixture inspector.
   *
   * @var \Acquia\Orca\Fixture\FixtureInspector
   */
  private $fixtureInspector;

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
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

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
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   */
  public function __construct(ConfigLoader $config_loader, Fixture $fixture, FixtureInspector $fixture_inspector, SymfonyStyle $output, ProcessRunner $process_runner, PackageManager $package_manager, SubextensionManager $subextension_manager) {
    $this->configLoader = $config_loader;
    $this->fixture = $fixture;
    $this->fixtureInspector = $fixture_inspector;
    $this->output = $output;
    $this->processRunner = $process_runner;
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
   * Enables the Acquia extensions.
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

    $this->output->section('Enabling Acquia modules & themes');
    $this->enableModules();
    $this->enableThemes();
  }

  /**
   * Enables the Acquia modules.
   */
  private function enableModules(): void {
    $module_list = $this->getAcquiaExtensionList(self::TYPE_MODULE);
    if (!$module_list) {
      return;
    }
    $this->processRunner->runFixtureVendorBin(array_merge([
      'drush',
      'pm:enable',
      '--yes',
    ], $module_list));
  }

  /**
   * Enables the Acquia themes.
   */
  private function enableThemes(): void {
    $theme_list = $this->getAcquiaExtensionList(self::TYPE_THEME);
    if (!$theme_list) {
      return;
    }
    $this->processRunner->runFixtureVendorBin([
      'drush',
      'theme:enable',
      '--yes',
      implode(',', $theme_list),
    ]);
  }

  /**
   * Gets the list of Acquia extensions to enable.
   *
   * @param string $extension_type
   *   The extension type: ::TYPE_MODULE or ::TYPE_THEME.
   *
   * @return string[]
   *   An indexed array of Acquia extension machine names.
   */
  private function getAcquiaExtensionList(string $extension_type): array {
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
   * @param \Acquia\Orca\Fixture\Package $package
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
      && $this->fixtureInspector->getInstalledPackageVersion($package->getPackageName());
  }

}
