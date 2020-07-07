<?php

namespace Acquia\Orca\Server;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Provides a base server implementation.
 */
abstract class ServerBase implements ServerInterface {

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The server process.
   *
   * @var \Symfony\Component\Process\Process|null
   */
  protected $process;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, ProcessRunner $process_runner) {
    $this->fixture = $fixture_path_handler;
    $this->processRunner = $process_runner;
  }

  /**
   * Creates the server process.
   *
   * @return \Symfony\Component\Process\Process
   *   The server process.
   */
  abstract protected function createProcess(): Process;

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    $this->process = $this->createProcess();
    $this->process
      ->setTimeout(NULL)
      ->setIdleTimeout(NULL)

      // This is necessary for processes to run asynchronously.
      ->disableOutput()

      ->start();
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    if ($this->process) {
      $this->process->stop();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function wait(): void {
    if ($this->process) {
      $this->process->wait();
    }
  }

  /**
   * Gets the fixture.
   *
   * @return \Acquia\Orca\Filesystem\FixturePathHandler
   *   The fixture path handler.
   */
  protected function getFixture(): FixturePathHandler {
    return $this->fixture;
  }

  /**
   * Gets the process runner.
   *
   * @return \Acquia\Orca\Utility\ProcessRunner
   *   The process runner.
   */
  protected function getProcessRunner(): ProcessRunner {
    return $this->processRunner;
  }

}
