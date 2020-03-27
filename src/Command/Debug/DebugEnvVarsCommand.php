<?php

namespace Acquia\Orca\Command\Debug;

use Acquia\Orca\Enum\StatusCode;
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
    'ORCA_SUT_BRANCH',
    'ORCA_SUT_DIR',
    'ORCA_SUT_NAME',
    'ORCA_TELEMETRY_ENABLE',
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
        \Env::get($var) ?: '~',
      ];
    }
    return $rows;
  }

}
