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
   */
  abstract protected function createProcess(): Process;

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    $this->process = $this->createProcess();
    $this->process->start();
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
   * Gets the fixture.
   *
   * @return \Acquia\Orca\Fixture\Fixture
   */
  protected function getFixture(): Fixture {
    return $this->fixture;
  }

  /**
   * Gets the process runner.
   *
   * @return \Acquia\Orca\Utility\ProcessRunner
   */
  protected function getProcessRunner(): ProcessRunner {
    return $this->processRunner;
  }

}
