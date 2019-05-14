<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Installs Acquia Drupal modules.
 */
class AcquiaModuleEnabler {

  use SutSettingsTrait;

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
   * The submodule manager.
   *
   * @var \Acquia\Orca\Fixture\SubmoduleManager
   */
  private $submoduleManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Fixture\SubmoduleManager $submodule_manager
   *   The submodule manager.
   */
  public function __construct(ConfigLoader $config_loader, Fixture $fixture, SymfonyStyle $output, ProcessRunner $process_runner, PackageManager $package_manager, SubmoduleManager $submodule_manager) {
    $this->configLoader = $config_loader;
    $this->fixture = $fixture;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->packageManager = $package_manager;
    $this->submoduleManager = $submodule_manager;
  }

  /**
   * Enables modules.
   *
   * @throws \Exception
   */
  public function enable(): void {
    $this->getSutSettingsFromFixture();
    $this->enableAcquiaModules();
  }

  /**
   * Gets the SUT settings from the fixture.
   *
   * @throws \Exception
   */
  private function getSutSettingsFromFixture(): void {
    $config = $this->configLoader->load($this->fixture->getPath('composer.json'));
    $this->setSut($config->get('extra.orca.sut'));
    $this->setSutOnly($config->get('extra.orca.is-sut-only', FALSE));
  }

  /**
   * Enables the Acquia modules.
   */
  private function enableAcquiaModules(): void {
    if ($this->isSutOnly && ($this->sut->getType() !== 'drupal-module')) {
      $this->output->warning('No modules to enable because the fixture is SUT-only and the SUT is not a Drupal module');
      return;
    }

    $this->output->section('Enabling Acquia modules');
    $module_list = $this->getAcquiaModuleList();
    $this->processRunner->runFixtureVendorBin(array_merge([
      'drush',
      'pm:enable',
      '--yes',
    ], $module_list));
  }

  /**
   * Gets the list of Acquia modules to enable.
   *
   * @return string[]
   *   An indexed array of Acquia module machine names.
   */
  private function getAcquiaModuleList(): array {
    $module_list = [];

    $top_level_packages = $this->packageManager->getMultiple();
    if ($this->isSutOnly) {
      $top_level_packages = [$this->sut];
    }

    foreach ($top_level_packages as $package) {
      if ($package->getType() === 'drupal-module') {
        $module_list[] = $package->getProjectName();
      }

      foreach ($this->submoduleManager->getByParent($package) as $submodule) {
        if (!$submodule->shouldGetEnabled()) {
          continue;
        }
        $module_list[] = $submodule->getProjectName();
      }
    }

    return $module_list;
  }

}
