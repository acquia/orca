<?php

namespace Acquia\Orca\Console\Command\Internal;

use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Helper\Log\TelemetryClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class InternalCreateDataSetCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  public static $defaultName = 'internal:create-dataset';

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
   */
  public function __construct(TelemetryClient $telemetry_client) {
    $this->telemetryClient = $telemetry_client;

    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setDescription('Creates/updates a domo data stream based on current telemetry config yml')
      ->addOption('simulate', ['s'], InputOption::VALUE_NONE, 'Run in simulated mode: show what would be created instead of actually creating the stream')
      ->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $simulate = $input->getOption('simulate');

    // Get the Telemetry Config
    $data = $this->telemetryClient->getLocalConfig();

    if ($simulate) {
      $output->write(print_r($data, TRUE));
      return StatusCode::OK;
    }

    if ($this->telemetryClient->isReady()) {
      if (empty($data['dataset_id'])) {
        $result = $this->telemetryClient->createDataSet($data);
      }
      else {
        $result = $this->telemetryClient->updateDataSet($data['dataset_id'], $data);
      }
      if (isset($result['error'])) {
        $output->write(print_r($result, TRUE));
        return StatusCode::ERROR;
      }

      // Save the results from the creation request back to the Yaml config.
      $data['dataset_id'] = $result['id'];
      $data['createdAt'] = $result['createdAt'];
      $data['updatedAt'] = $result['updatedAt'];
      $data['pdpEnabled'] = $result['pdpEnabled'];

      $this->telemetryClient->saveLocalConfig($data);
      return StatusCode::OK;
    }
    $output->write(print_r("Error: Unable to connect to DOMO, check your credentials and try again.", TRUE));
    return StatusCode::ERROR;
  }
}
