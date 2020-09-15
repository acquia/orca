<?php

namespace Acquia\Orca\Helper\Log;

use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use WoganMay\DomoPHP\DomoPHP;

/**
 * Provides a telemetry client.
 */
class TelemetryClient {

  /**
   * The Domo client.
   *
   * @var \WoganMay\DomoPHP\DomoPHP
   */
  private $domoClient;

  /**
   * The Telemetry Config Location.
   *
   * @var string
   */
  private $telemetryConfigFilename;

  /**
   * The Telemetry local config data.
   *
   * @var array
   */
  private $localConfig;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Yaml\Parser $parser
   *   The YAML parser.
   * @param bool|null $telemetry_is_enabled
   *   TRUE if telemetry is enabled or FALSE if not.
   * @param string|null $domo_api_key
   *   The Domo API key.
   * @param string|null $domo_user_id
   *   The Domo user ID, typically created by combining the SUT name and
   *   branch, e.g., "drupal/example:8.x-1.x".
   */
  public function __construct(OrcaPathHandler $orca_path_handler, Parser $parser, bool $telemetry_is_enabled, string $telemetry_config, ?string $domo_api_key = NULL, ?string $domo_user_id = NULL) {
    $this->telemetryConfigFilename = $orca_path_handler->getPath($telemetry_config);
    $this->localConfig = $parser->parseFile($this->telemetryConfigFilename);

    if (!$telemetry_is_enabled || !$domo_api_key || !$domo_user_id) {
      return;
    }
    $domoClient = new DomoPHP($domo_user_id, $domo_api_key);
    $this->domoClient = $domoClient;
  }

  /**
   * Determines whether or not telemetry is ready.
   *
   * @return bool
   *   TRUE if telemetry is ready or FALSE if not.
   */
  public function isReady(): bool {
    return (bool) $this->domoClient;
  }

  /**
   * @param $data
   * Dataset in JSON format
   *
   * @return object|array The result with the dataset ID, or an error.
   */
  public function createDataSet(array $data) {
    // Strip the name and data columns
    try {
      $result = $this->domoClient->API->DataSet->createDataSet($data['name'], $this->stripDescription($data['schema']['columns']), $data['description']);
    }
    catch (GuzzleException $exception) {
      return ['error' =>  "There were errors creating the dataset: ". $exception->getMessage()];
    }
    return (array) $result;
  }

  /**
   * @param string $uuid
   * Dataset UUID.
   * @param array $data
   * Dataset in JSON format
   *
   * @return object|array The result with the dataset ID, or an error.
   */
  public function updateDataSet(string $uuid, array $data) {
    $columns['schema']['columns'] = $this->stripDescription($data['schema']['columns']);
    try {
      $result = $this->domoClient->API->DataSet->updateDataSet($uuid, ['name' => $data['name'], 'description' => $data['description'], 'schema' => $columns['schema']]);
    }
    catch (\Exception $exception) {
      return ['error' =>  "There were errors updating the dataset: ". $exception->getMessage()];
    }
    return (array) $result;
  }

  /**
   * Get the DataSet config from DOMO
   */
  public function getDataSet(string $uuid) {
    try {
      $result = $this->domoClient->API->DataSet->getDataSet($uuid);
    }
    catch (\Exception $exception) {
      return ['error' =>  "There were errors updating the dataset: ". $exception->getMessage()];
    }
    return (array) $result;
  }

  /**
   * Get the local telemetry config file.
   * @return array|mixed|\stdClass|string|\Symfony\Component\Yaml\Tag\TaggedValue|null
   */
  public function getLocalConfig() {
    return $this->localConfig;
  }

  /**
   * Saves the local telemetry config with incoming yaml input.
   * @param array $config
   */
  public function saveLocalConfig($config) {
    $this->localConfig = $config;
    $telemetry_yaml = Yaml::dump($config,6,2);
    file_put_contents($this->telemetryConfigFilename, $telemetry_yaml);

    return $config;
  }

  /**
   * Grabs the Properties from domo and sorts them into an ordered array.
   * @return array
   */
  private function getDomoProperties() {
    $properties = [];

    $dataset = $this->getDataSet($this->localConfig['dataset_id']);
    foreach ($dataset['schema']->columns AS $order => $property) {
      $properties[$order] = $property->name;
    }
    return $properties;
  }


  /**
   * Strips the description from a column before sending it to DOMO.
   * @param array $data
   */
  private function stripDescription(array $data) :array {
    foreach ($data as $k => $row) {
      unset($data[$k]['description']);
    }
    return $data;
  }

  /**
   * Logs an event.
   *
   * @param string $type
   *   The event name, e.g., "Fixture created". Legacy, will be removed later.
   * @param array $properties
   *   An associative array of key/value pairs corresponding to properties or
   *   attributes of the event.
   */
  public function logEvent(string $type, array $properties = []) {
    if (!$this->domoClient) {
      return;
    }

    // Fetch the properties from the active dataset
    $domo_properties = $this->getDomoProperties();
    $csv = [];
    foreach($domo_properties AS $column => $property) {
      $csv[$column] = $properties[$property] ?? "";
    }
    # Generate CSV data from array. Use php built in converter.
    $fh = fopen('php://temp', 'rw');

    # write out the data
    foreach ( $csv as $row ) {
      fputcsv($fh, $row);
    }
    rewind($fh);
    $data_to_import = stream_get_contents($fh);
    fclose($fh);

    try {
      $result = $this->domoClient->API->DataSet->importDataSet($this->localConfig['dataset_id'], $data_to_import);
    }
    catch (\Exception $exception) {
      return ['error' =>  "There were errors importing data: ". $exception->getMessage()];
    }
    return (array) $result;
  }
}
