<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ConfigFileOverrider;
use Acquia\Orca\Utility\ProcessRunner;

/**
 * Provides a base task implementation.
 */
abstract class TaskBase implements TaskInterface {

  /**
   * The config file overrider.
   *
   * @var \Acquia\Orca\Utility\ConfigFileOverrider
   */
  protected $configFileOverrider;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  protected $fixture;

  /**
   * A filesystem path.
   *
   * @var string
   */
  protected $path;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  protected $processRunner;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  protected $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigFileOverrider $config_file_overrider
   *   The config file overrider.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(ConfigFileOverrider $config_file_overrider, Fixture $fixture, ProcessRunner $process_runner, string $project_dir) {
    $this->configFileOverrider = $config_file_overrider;
    $this->fixture = $fixture;
    $this->processRunner = $process_runner;
    $this->projectDir = $project_dir;
  }

  /**
   * Gets the path.
   *
   * @return string
   */
  public function getPath(): string {
    if (!$this->path) {
      throw new \LogicException(sprintf('Path not set in %s:%s().', get_class($this), debug_backtrace()[1]['function']));
    }
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath(?string $path): TaskInterface {
    $this->path = $path;
    return $this;
  }

}
