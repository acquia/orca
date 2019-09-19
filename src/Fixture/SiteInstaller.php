<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Console\Style\SymfonyStyle;

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
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\AcquiaExtensionEnabler $acquia_extension_enabler
   *   The Acquia extension enabler.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   */
  public function __construct(AcquiaExtensionEnabler $acquia_extension_enabler, Fixture $fixture, ProcessRunner $process_runner, SymfonyStyle $output) {
    $this->acquiaExtensionEnabler = $acquia_extension_enabler;
    $this->fixture = $fixture;
    $this->output = $output;
    $this->processRunner = $process_runner;
  }

  /**
   * Installs the site.
   *
   * @param string $profile
   *   The machine name of the profile to install, e.g., "lightning".
   *
   * @throws \Exception
   */
  public function install(string $profile): void {
    $this->installDrupal($profile);
    $this->acquiaExtensionEnabler->enable();
  }

  /**
   * Installs Drupal.
   *
   * @param string $profile
   *   The machine name of the profile to install, e.g., "lightning".
   */
  private function installDrupal(string $profile): void {
    $this->output->section('Installing Drupal');
    $this->processRunner->runFixtureVendorBin([
      'drush',
      'site:install',
      $profile,
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

}
