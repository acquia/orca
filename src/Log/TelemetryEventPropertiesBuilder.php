<?php

namespace Acquia\Orca\Log;

use Acquia\Orca\Enum\TelemetryEventName;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask;
use Symfony\Component\Filesystem\Filesystem;

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
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The PHP LOC task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask
   */
  private $phpLocTask;

  /**
   * The event properties.
   *
   * @var array
   */
  private $properties = [];

  /**
   * Constructs an instance.
   *
   * @param \Env $env
   *   The environment variables service.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask $php_loc_task
   *   The PHP LOC task.
   */
  public function __construct(\Env $env, Filesystem $filesystem, PhpLocTask $php_loc_task) {
    $this->env = $env;
    $this->filesystem = $filesystem;
    $this->phpLocTask = $php_loc_task;
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
    $this->properties = [];
    $this->addEnvironmentVariables();
    $this->addStaticAnalysisResults();
    return $this->properties;
  }

  /**
   * Adds environment variables to the event properties.
   */
  protected function addEnvironmentVariables(): void {
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
    $this->properties = array_merge($this->properties, $properties);
  }

  /**
   * Adds static analysis results to the event properties.
   */
  private function addStaticAnalysisResults(): void {
    $path = $this->phpLocTask->getJsonLogPath();

    if (!$this->filesystem->exists($path)) {
      return;
    }

    $json = file_get_contents($path);
    $data = json_decode($json);

    if (json_last_error()) {
      return;
    }

    $this->properties['phploc'] = $data;
  }

}
