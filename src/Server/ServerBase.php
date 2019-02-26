<?php

namespace Acquia\Orca\Server;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Provides a base server implementation.
 */
abstract class ServerBase implements ServerInterface {

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
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
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Fixture $fixture, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
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
   * @return \Acquia\Orca\Fixture\Fixture
   *   The fixture.
   */
  protected function getFixture(): Fixture {
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
