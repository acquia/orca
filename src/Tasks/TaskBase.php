<?php

namespace Acquia\Orca\Tasks;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Symfony\Component\Finder\Finder;

/**
 * Provides a base task implementation.
 *
 * @property \Symfony\Component\Finder\Finder $finder
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\ProcessRunner $processRunner
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
   */
  public function __construct(Finder $finder, Fixture $fixture, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->finder = $finder;
    $this->processRunner = $process_runner;
  }

  /**
   * Gets the path.
   *
   * @return string
   */
  public function getPath(): string {
    if (!$this->path) {
      throw new \LogicException('Path not set.');
    }
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath(string $path): TaskInterface {
    $this->path = $path;
    return $this;
  }

}
