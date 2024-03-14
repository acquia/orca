<?php

namespace Acquia\Orca\Domain\Drush;

use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Provides a facade for Drush.
 */
class DrushFacade {

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
    $this->processRunner->runFixtureVendorBin(array_merge([
      'drush',
      'pm:enable',
      '--yes',
    ], $list));
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
   * @throws \Acquia\Orca\Exception\OrcaParseError
   *   In case of invalid output.
   */
  public function getDrushStatus(): array {
    $process = $this->processRunner->createFixtureVendorBinProcess([
      'drush',
      'core:status',
      '--format=json',
    ]);
    $process->run();
    $output = $process->getOutput();
    $json = $this->extractJson($output);
    $data = json_decode($json, TRUE);

    if (json_last_error()) {
      throw new OrcaParseError(sprintf(
        'Invalid Drush JSON output: (%s) %s',
        json_last_error(),
        json_last_error_msg()
      ));
    }

    return $data;
  }

  /**
   * Sometimes drush returns a HTML response, this method extracts the JSON.
   *
   * @param string $json
   *   The response from drush.
   *
   * @return string
   *   The JSON response extracted.
   */
  public function extractJson(string $json): string {
    return substr($json, strpos($json, "{"));
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
