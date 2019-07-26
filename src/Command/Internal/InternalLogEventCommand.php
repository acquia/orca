<?php

namespace Acquia\Orca\Command\Internal;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Enum\TelemetryEventName;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Log\TelemetryEventPropertiesBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class InternalLogEventCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  public static $defaultName = 'internal:log-event';

  /**
   * The telemetry event properties builder.
   *
   * @var \Acquia\Orca\Log\TelemetryEventPropertiesBuilder
   */
  private $telemetryEventPropertiesBuilder;

  /**
   * The telemetry client.
   *
   * @var \Acquia\Orca\Log\TelemetryClient
   */
  private $telemetryClient;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Log\TelemetryClient $telemetry_client
   *   The telemetry client.
   * @param \Acquia\Orca\Log\TelemetryEventPropertiesBuilder $telemetry_event_properties_builder
   *   The telemetry event builder.
   */
  public function __construct(TelemetryClient $telemetry_client, TelemetryEventPropertiesBuilder $telemetry_event_properties_builder) {
    $this->telemetryClient = $telemetry_client;
    $this->telemetryEventPropertiesBuilder = $telemetry_event_properties_builder;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function configure() {
    $this
      ->setAliases(['log'])
      ->setDescription('Logs an event if telemetry is enabled.')
      ->addArgument('name', InputArgument::REQUIRED, implode(PHP_EOL, [
        'The event name:',
        sprintf('- %s: %s', TelemetryEventName::TRAVIS_CI_JOB()->getKey(), TelemetryEventName::TRAVIS_CI_JOB),
        sprintf('- %s: %s', TelemetryEventName::TEST()->getKey(), TelemetryEventName::TEST),
      ]))
      ->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->telemetryClient->isReady()) {
      $output->writeln([
        'Notice: Nothing logged. Telemetry is disabled.',
        'Hint: https://github.com/acquia/orca/blob/master/docs/advanced-usage.md#ORCA_TELEMETRY_ENABLE',
      ]);
      return StatusCodes::OK;
    }

    $name = $input->getArgument('name');

    if (!TelemetryEventName::isValidKey($name)) {
      $output->writeln([
        sprintf('Error: Invalid value for "name" argument: "%s".', $name),
        sprintf('Hint: Acceptable values are "%s".', implode('", "', TelemetryEventName::keys())),
      ]);
      return StatusCodes::ERROR;
    }

    $name = call_user_func([TelemetryEventName::class, $name]);
    $properties = $this->telemetryEventPropertiesBuilder->build($name);
    $this->telemetryClient->logEvent($name, $properties);

    return StatusCodes::OK;
  }

}
