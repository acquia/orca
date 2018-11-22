<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Symfony\Component\Finder\Finder;

/**
 * Provides a base task implementation.
 *
 * @property \Symfony\Component\Finder\Finder $finder
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\ProcessRunner $processRunner
 * @property string $projectDir
 */
abstract class TaskBase implements TaskInterface {

  /**
   * A filesystem path.
   *
   * @var string
   */
  private $path;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Finder\Finder $finder
   *   The finder.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Finder $finder, Fixture $fixture, ProcessRunner $process_runner, string $project_dir) {
    $this->fixture = $fixture;
    $this->finder = clone($finder);
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
