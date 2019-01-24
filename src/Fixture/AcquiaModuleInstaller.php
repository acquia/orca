<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Installs Acquia Drupal modules.
 */
class AcquiaModuleInstaller {

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
   * Installs modules.
   *
   * @throws \Exception
   */
  public function install(): void {
    $this->getSutSettingsFromFixture();
    $this->installAcquiaModules();
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
   * Installs the Acquia modules.
   */
  private function installAcquiaModules(): void {
    if ($this->isSutOnly && ($this->sut->getType() !== 'drupal-module')) {
      $this->output->warning('No modules to install because the fixture is SUT-only and the SUT is not a Drupal module');
      return;
    }

    $this->output->section('Installing Acquia modules');
    $module_list = $this->getAcquiaModuleList();
    $process = $this->processRunner->createFixtureVendorBinProcess(array_merge([
      'drush',
      'pm-enable',
      '--yes',
    ], $module_list));
    $this->processRunner->run($process, $this->fixture->getPath());
    $this->output->success('Modules installed');
  }

  /**
   * Gets the list of Acquia modules to install.
   *
   * @return string[]
   */
  private function getAcquiaModuleList(): array {
    if ($this->isSutOnly) {
      $module_list = [$this->sut->getProjectName()];
      foreach ($this->submoduleManager->getByParent($this->sut) as $submodule) {
        $module_list[] = $submodule->getProjectName();
      }
      return $module_list;
    }

    $module_list = array_values($this->packageManager->getMultiple('drupal-module', 'getProjectName'));
    foreach ($this->submoduleManager->getAll() as $submodule) {
      $module_list[] = $submodule->getProjectName();
    }
    return $module_list;
  }

}
