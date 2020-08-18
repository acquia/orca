<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Console\Helper\StatusCode;
use Env;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugEnvVarsCommand extends Command {

  private const VARS = [
    'ORCA_AMPLITUDE_API_KEY',
    'ORCA_AMPLITUDE_USER_ID',
    'ORCA_CUSTOM_FIXTURE_INIT_ARGS',
    'ORCA_CUSTOM_TESTS_RUN_ARGS',
    'ORCA_FIXTURE_DIR',
    'ORCA_FIXTURE_PROFILE',
    'ORCA_JOB',
    'ORCA_PACKAGES_CONFIG',
    'ORCA_PACKAGES_CONFIG_ALTER',
    'ORCA_PHPCS_STANDARD',
    'ORCA_ROOT',
    'ORCA_ENABLE_NIGHTWATCH',
    'ORCA_SUT_BRANCH',
    'ORCA_SUT_DIR',
    'ORCA_SUT_HAS_NIGHTWATCH_TESTS',
    'ORCA_SUT_MACHINE_NAME',
    'ORCA_SUT_NAME',
    'ORCA_TELEMETRY_ENABLE',
    'ORCA_YARN_DIR',
    'DRUPAL_NIGHTWATCH_IGNORE_DIRECTORIES',
    'DRUPAL_NIGHTWATCH_OUTPUT',
    'DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY',
    'DRUPAL_TEST_BASE_URL',
    'DRUPAL_TEST_CHROMEDRIVER_AUTOSTART',
    'DRUPAL_TEST_DB_URL',
    'DRUPAL_TEST_WEBDRIVER_CHROME_ARGS',
    'DRUPAL_TEST_WEBDRIVER_HOSTNAME',
    'DRUPAL_TEST_WEBDRIVER_PORT',
  ];

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'debug:env-vars';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['vars'])
      ->setDescription('Displays ORCA environment variables');
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    (new Table($output))
      ->setHeaders($this->getHeaders())
      ->setRows($this->getRows())
      ->render();
    return StatusCode::OK;
  }

  /**
   * Gets the table headers.
   *
   * @return string[]
   *   An array of headers.
   */
  private function getHeaders(): array {
    return [
      'Variable',
      'Value',
    ];
  }

  /**
   * Gets the table rows.
   *
   * @return string[]
   *   An array of table rows.
   */
  private function getRows(): array {
    $rows = [];
    foreach (self::VARS as $var) {
      $rows[] = [
        $var,
        Env::get($var) ?: '~',
      ];
    }
    return $rows;
  }

}
