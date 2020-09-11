<?php

namespace Acquia\Orca\Drush;

use Acquia\Orca\Helper\Exception\ParseError;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Provides a facade for encapsulating Drush interactions against the fixture.
 */
class Drush {

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ProcessRunner $process_runner) {
    $this->processRunner = $process_runner;
  }

  /**
   * Enables a list of modules.
   *
   * @param string[] $list
   *   A list of module machine names.
   */
  public function enableModules(array $list): void {
    $this->processRunner->runFixtureVendorBin([
      'drush',
      'pm:enable',
      '--yes',
      implode(',', $list),
    ]);
  }

  /**
   * Enables a list of themes.
   *
   * @param string[] $list
   *   A list of theme machine names.
   */
  public function enableThemes(array $list): void {
    $this->processRunner->runFixtureVendorBin([
      'drush',
      'theme:enable',
      implode(',', $list),
    ]);
  }

  /**
   * Gets the Drush status.
   *
   * @return array
   *   The Drush status data.
   *
   * @throws \Acquia\Orca\Helper\Exception\ParseError
   *   In case of invalid output.
   */
  public function getDrushStatus(): array {
    $process = $this->processRunner->createFixtureVendorBinProcess([
      'drush',
      'core:status',
      '--format=json',
    ]);
    $process->run();
    $json = $process->getOutput();
    $data = json_decode($json, TRUE);

    if (json_last_error()) {
      throw new ParseError('Invalid Drush output.');
    }

    return $data;
  }

  /**
   * Installs Drupal.
   *
   * @param string $profile
   *   The installation profile.
   */
  public function installDrupal(string $profile): void {
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

  /**
   * Enables the admin theme on node forms.
   *
   * Checks the "Use the administration theme when editing or creating
   * content" checkbox.
   */
  public function setNodeFormsUseAdminTheme(): void {
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
