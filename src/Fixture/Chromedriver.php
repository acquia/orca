<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Provides Chromedriver.
 */
class Chromedriver {

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The chromedriver process.
   *
   * @var \Symfony\Component\Process\Process
   */
  private $process;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Fixture $fixture, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->processRunner = $process_runner;
  }

  /**
   * Starts chromedriver.
   */
  public function start() {
    $this->process = $this->processRunner->createOrcaVendorBinProcess([
      'chromedriver',
      '--port=4444',
    ])
      ->setTimeout(NULL)
      ->setIdleTimeout(NULL);
    $this->process->start();
    // Give the process a chance to bootstrap before releasing the thread to
    // code that will depend on it.
    sleep(3);
  }

  /**
   * Stops chromedriver.
   */
  public function stop() {
    if ($this->process) {
      $this->process->stop();
    }
  }

}
