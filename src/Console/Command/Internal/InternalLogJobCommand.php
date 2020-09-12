<?php

namespace Acquia\Orca\Console\Command\Internal;

use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Enum\TelemetryEventNameEnum;
use Acquia\Orca\Helper\Log\TelemetryClient;
use Acquia\Orca\Helper\Log\TelemetryEventPropertiesBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class InternalLogJobCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  public static $defaultName = 'internal:log-job';

  /**
   * The telemetry event properties builder.
   *
   * @var \Acquia\Orca\Helper\Log\TelemetryEventPropertiesBuilder
   */
  private $telemetryEventPropertiesBuilder;

  /**
   * The telemetry client.
   *
   * @var \Acquia\Orca\Helper\Log\TelemetryClient
   */
  private $telemetryClient;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Log\TelemetryClient $telemetry_client
   *   The telemetry client.
   * @param \Acquia\Orca\Helper\Log\TelemetryEventPropertiesBuilder $telemetry_event_properties_builder
   *   The telemetry event builder.
   */
  public function __construct(TelemetryClient $telemetry_client, TelemetryEventPropertiesBuilder $telemetry_event_properties_builder) {
    $this->telemetryClient = $telemetry_client;
    $this->telemetryEventPropertiesBuilder = $telemetry_event_properties_builder;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['log'])
      ->setDescription('Logs an event if telemetry is enabled.')
      ->addOption('simulate', ['s'], InputOption::VALUE_NONE, 'Run in simulated mode: show what would be logged instead of actually logging it')
      ->addOption('test', NULL, InputOption::VALUE_NONE, 'Send a test event for debugging')
      ->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $simulate = $input->getOption('simulate');

    if (!$simulate && !$this->telemetryClient->isReady()) {
      $output->writeln([
        'Notice: Nothing logged. Telemetry is disabled.',
        'Hint: https://github.com/acquia/orca/blob/master/docs/advanced-usage.md#ORCA_TELEMETRY_ENABLE',
      ]);
      return StatusCodeEnum::OK;
    }

    $name = $this->getEventName((bool) $input->getOption('test'));
    $properties = $this->telemetryEventPropertiesBuilder->build($name);

    if ($simulate) {
      $output->write(print_r($properties, TRUE));
      return StatusCodeEnum::OK;
    }

    $this->telemetryClient->logEvent($name->getValue(), $properties);

    return StatusCodeEnum::OK;
  }

  /**
   * Gets the telemetry event name.
   *
   * @param bool $test_option
   *   The "--test" option value.
   *
   * @return \Acquia\Orca\Enum\TelemetryEventNameEnum
   *   The telemetry event name.
   */
  protected function getEventName(bool $test_option): TelemetryEventNameEnum {
    $name = ($test_option) ? TelemetryEventNameEnum::TEST : TelemetryEventNameEnum::TRAVIS_CI_JOB;
    return new TelemetryEventNameEnum($name);
  }

}
