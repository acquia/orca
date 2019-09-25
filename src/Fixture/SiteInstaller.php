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
 * Installs a site and enables Acquia extensions.
 */
class SiteInstaller {

  /**
   * The Acquia extension enabler.
   *
   * @var \Acquia\Orca\Fixture\AcquiaExtensionEnabler
   */
  private $acquiaExtensionEnabler;

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
   * The path the Testing profile is backed up to.
   *
   * @var string|null
   */
  private $profileBackupPath;

  /**
   * The path of the Testing profile.
   *
   * @var string|null
   */
  private $profilePath;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  private $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\AcquiaExtensionEnabler $acquia_extension_enabler
   *   The Acquia extension enabler.
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
  public function __construct(AcquiaExtensionEnabler $acquia_extension_enabler, Filesystem $filesystem, Fixture $fixture, ProcessRunner $process_runner, string $project_dir, SymfonyStyle $output) {
    $this->acquiaExtensionEnabler = $acquia_extension_enabler;
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
    $this->prepareTestingProfile();
    $this->installDrupal();
    $this->restoreTestingProfile();
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
      $this->profile = 'testing';
      return;
    }

    $this->isOrcaProfile = FALSE;
    $this->profile = $profile;
  }

  /**
   * Prepares the Testing profile for installation.
   *
   * Augments the Testing profile, if selected, with some basic theme settings
   * from Standard.
   */
  private function prepareTestingProfile(): void {
    if (!$this->isOrcaProfile) {
      return;
    }

    $this->backupTestingProfile();
    $this->addDependencies();
    $this->copyStandardThemeSettings();
  }

  /**
   * Backs up the Testing profile.
   */
  private function backupTestingProfile(): void {
    $this->profilePath = $this->fixture->getPath('docroot/core/profiles/testing');
    $this->profileBackupPath = sprintf('%s/var/backup/testing-%s', $this->projectDir, uniqid());
    $this->filesystem->mirror($this->profilePath, $this->profileBackupPath);
  }

  /**
   * Adds dependencies to the Testing profile.
   */
  private function addDependencies(): void {
    $path = $this->fixture->getPath('docroot/core/profiles/testing/testing.info.yml');
    $info_file = (Config::load($path));

    $this->addItems($info_file, 'install', [
      'block',
      'dblog',
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
   * Copies the theme and block settings from the Standard profile to Testing.
   */
  private function copyStandardThemeSettings(): void {
    // Ensure no theme settings in the install config conflict with those about
    // to be copied into the optional config.
    $this->filesystem->remove($this->fixture->getPath('docroot/core/profiles/testing/config/install/system.theme.yml'));

    $files = (new Finder())
      ->files()
      ->in($this->fixture->getPath('docroot/core/profiles/standard/config/install'))
      ->name([
        '/block.block.seven_.*\.yml/',
        'system.theme.yml',
      ]);
    /** @var \SplFileInfo $file */
    foreach ($files as $file) {
      $target_pathname = str_replace("/standard/config/install", "/testing/config/optional", $file->getPathname());
      $this->filesystem->copy($file->getPathname(), $target_pathname, TRUE);
    }
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
   * Restores the Testing profile.
   */
  private function restoreTestingProfile(): void {
    if (!$this->isOrcaProfile) {
      return;
    }

    $this->filesystem->remove($this->profilePath);
    $this->filesystem->mirror($this->profileBackupPath, $this->profilePath);
    $this->filesystem->remove($this->profileBackupPath);
  }

  /**
   * Enables Acquia Drupal extensions.
   *
   * @throws \Exception
   */
  private function enableExtensions(): void {
    $this->acquiaExtensionEnabler->enable();

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
