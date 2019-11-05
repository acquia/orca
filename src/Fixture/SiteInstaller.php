<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;
use Noodlehaus\Config;
use Noodlehaus\Writer\Yaml;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Installs a site and enables company extensions.
 */
class SiteInstaller {

  /**
   * The profile to base the "orca" pseudo-profile on.
   *
   * @var string
   */
  private $baseProfile = 'minimal';

  /**
   * The path the base profile is backed up to.
   *
   * @var string|null
   */
  private $baseProfileBackupPath;

  /**
   * The path of the base profile.
   *
   * @var string|null
   */
  private $baseProfilePath;

  /**
   * The company extension enabler.
   *
   * @var \Acquia\Orca\Fixture\CompanyExtensionEnabler
   */
  private $companyExtensionEnabler;

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
   * Whether or not the ORCA profile is being installed.
   *
   * @var bool
   */
  private $isOrcaProfile = FALSE;

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
   * The profile to install.
   *
   * @var string|null
   */
  private $profile;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  private $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\CompanyExtensionEnabler $company_extension_enabler
   *   The company extension enabler.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param string $project_dir
   *   The ORCA project directory.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   */
  public function __construct(CompanyExtensionEnabler $company_extension_enabler, Filesystem $filesystem, Fixture $fixture, ProcessRunner $process_runner, string $project_dir, SymfonyStyle $output) {
    $this->companyExtensionEnabler = $company_extension_enabler;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->projectDir = $project_dir;
  }

  /**
   * Installs the site.
   *
   * @param string $profile
   *   The machine name of the profile to install, e.g., "minimal".
   *
   * @throws \Exception
   */
  public function install(string $profile): void {
    $this->setProfile($profile);
    $this->prepareBaseProfile();
    $this->installDrupal();
    $this->restoreBaseProfile();
    $this->enableExtensions();
  }

  /**
   * Sets the installation profile.
   *
   * @param string $profile
   *   The installation profile machine name, e.g., "minimal".
   */
  private function setProfile(string $profile): void {
    if ($profile === 'orca') {
      $this->isOrcaProfile = TRUE;
      $this->profile = $this->baseProfile;
      return;
    }

    $this->isOrcaProfile = FALSE;
    $this->profile = $profile;
  }

  /**
   * Prepares the base profile for installation.
   *
   * If the "orca" profile is selected, augments the base profile with some
   * basic theme settings from Standard.
   */
  private function prepareBaseProfile(): void {
    if (!$this->isOrcaProfile) {
      return;
    }

    $this->backupBaseProfile();
    $this->addDependencies();
    $this->augmentThemeSettings();
  }

  /**
   * Backs up the base profile.
   */
  private function backupBaseProfile(): void {
    $this->baseProfilePath = $this->fixture->getPath("docroot/core/profiles/{$this->baseProfile}");
    $this->baseProfileBackupPath = "{$this->projectDir}/var/backup/{$this->baseProfile}-" . uniqid();
    $this->filesystem->mirror($this->baseProfilePath, $this->baseProfileBackupPath);
  }

  /**
   * Adds dependencies to the base profile.
   */
  private function addDependencies(): void {
    $path = $this->fixture->getPath("docroot/core/profiles/{$this->baseProfile}/{$this->baseProfile}.info.yml");
    $info_file = (Config::load($path));

    $this->addItems($info_file, 'install', [
      'help',
      'toolbar',
    ]);
    $this->addItems($info_file, 'themes', ['seven']);

    $info_file->toFile($path, new Yaml());
  }

  /**
   * Adds items to a given config file key value.
   *
   * @param \Noodlehaus\Config $file
   *   The config file.
   * @param string $key
   *   The key.
   * @param string[] $items
   *   The items to add.
   */
  private function addItems(Config $file, string $key, array $items): void {
    $original_value = $file->get($key, []);
    $value = array_merge($original_value, $items);
    $file->set($key, $value);
  }

  /**
   * Augments the theme settings.
   */
  private function augmentThemeSettings(): void {
    $this->copyStandardThemeSettings();
    $this->resetDefaultTheme();
  }

  /**
   * Copies the theme and block settings from the Standard profile.
   */
  private function copyStandardThemeSettings(): void {
    $files = (new Finder())
      ->files()
      ->in($this->fixture->getPath('docroot/core/profiles/standard/config/install'))
      ->name([
        '/block.block.seven_.*\.yml/',
        'system.theme.yml',
      ]);
    /** @var \SplFileInfo $file */
    foreach ($files as $file) {
      $target_pathname = str_replace("/standard/config/install", "/{$this->baseProfile}/config/optional", $file->getPathname());
      $this->filesystem->copy($file->getPathname(), $target_pathname, TRUE);
    }
  }

  /**
   * Resets the default theme.
   */
  private function resetDefaultTheme(): void {
    $path = $this->fixture->getPath("docroot/core/profiles/{$this->baseProfile}/config/optional/system.theme.yml");
    $config = (Config::load($path));
    $config->set('default', 'stark');
    $config->toFile($path, new Yaml());
  }

  /**
   * Installs Drupal.
   */
  private function installDrupal(): void {
    $this->output->section('Installing Drupal');
    $this->processRunner->runFixtureVendorBin([
      'drush',
      'site:install',
      $this->profile,
      "install_configure_form.update_status_module='[FALSE,FALSE]'",
      'install_configure_form.enable_update_status_module=NULL',
      '--site-name=ORCA',
      '--account-name=admin',
      '--account-pass=admin',
      '--no-interaction',
      '--verbose',
      '--ansi',
    ]);
  }

  /**
   * Restores the base profile.
   */
  private function restoreBaseProfile(): void {
    if (!$this->isOrcaProfile) {
      return;
    }

    $this->filesystem->remove($this->baseProfilePath);
    $this->filesystem->mirror($this->baseProfileBackupPath, $this->baseProfilePath);
    $this->filesystem->remove($this->baseProfileBackupPath);
  }

  /**
   * Enables company Drupal extensions.
   *
   * @throws \Exception
   */
  private function enableExtensions(): void {
    $this->companyExtensionEnabler->enable();

    if (!$this->isOrcaProfile) {
      return;
    }

    try {
      $this->processRunner->runFixtureVendorBin([
        'drush',
        'config:set',
        'node.settings',
        'use_admin_theme',
        TRUE,
      ]);
    }
    catch (ProcessFailedException $e) {
      // Swallow an irrelevant exception in case node.settings doesn't exist.
    }
  }

}
