<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Drush\Drush;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Noodlehaus\Config;
use Noodlehaus\Writer\Yaml;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
   * @var \Acquia\Orca\Domain\Fixture\CompanyExtensionEnabler
   */
  private $companyExtensionEnabler;

  /**
   * The Drush facade.
   *
   * @var \Acquia\Orca\Domain\Drush\Drush
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
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
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
   * The profile to install.
   *
   * @var string|null
   */
  private $profile;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Fixture\CompanyExtensionEnabler $company_extension_enabler
   *   The company extension enabler.
   * @param \Acquia\Orca\Domain\Drush\Drush $drush
   *   The Drush facade.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   */
  public function __construct(CompanyExtensionEnabler $company_extension_enabler, Drush $drush, Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, SymfonyStyle $output) {
    $this->companyExtensionEnabler = $company_extension_enabler;
    $this->drush = $drush;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->output = $output;
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
    $this->baseProfileBackupPath = $this->orca->getPath("var/backup/{$this->baseProfile}-{uniqid()}");
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
      $target_pathname = str_replace('/standard/config/install', "/{$this->baseProfile}/config/optional", $file->getPathname());
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
    $this->drush->installDrupal($this->profile);
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

    $this->drush->setNodeFormsUseAdminTheme();
  }

}
