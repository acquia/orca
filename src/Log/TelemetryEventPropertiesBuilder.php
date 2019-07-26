<?php

namespace Acquia\Orca\Log;

use Acquia\Orca\Enum\TelemetryEventName;

/**
 * Builds telemetry event properties.
 */
class TelemetryEventPropertiesBuilder {

  /**
   * The environment variables service.
   *
   * @var \Env
   */
  private $env;

  /**
   * Constructs an instance.
   *
   * @param \Env $env
   *   The environment variables service.
   */
  public function __construct(\Env $env) {
    $this->env = $env;
  }

  /**
   * Builds an array of event properties for the given event name.
   *
   * @param \Acquia\Orca\Enum\TelemetryEventName $name
   *   A telemetry event name.
   *
   * @return array
   *   An array of event properties.
   */
  public function build(TelemetryEventName $name): array {
    switch ($name->getValue()) {
      case TelemetryEventName::TRAVIS_CI_JOB:
        return $this->buildTravisCiJobProperties();

      case TelemetryEventName::TEST:
        return ['example' => TRUE];
    }
  }

  /**
   * Builds the event properties for the TRAVIS_CI_JOB event.
   *
   * @return array
   *   An array of properties.
   */
  protected function buildTravisCiJobProperties(): array {
    $properties = array_flip([
      'ORCA_JOB',
      'TRAVIS_COMMIT',
      'TRAVIS_COMMIT_MESSAGE',
      'TRAVIS_JOB_ID',
      'TRAVIS_JOB_NAME',
      'TRAVIS_JOB_NUMBER',
      'TRAVIS_JOB_WEB_URL',
      'TRAVIS_PHP_VERSION',
      'TRAVIS_REPO_SLUG',
      'TRAVIS_TEST_RESULT',
    ]);
    array_walk($properties, function (&$value, $key) {
      $value = $this->env::get($key);
    });
    return $properties;
  }

}
