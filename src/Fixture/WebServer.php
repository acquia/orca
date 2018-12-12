<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Provides a web server.
 *
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
class WebServer {

  /**
   * The web server process.
   *
   * @var \Symfony\Component\Process\Process
   */
  private $process;

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
   * Starts the web server.
   */
  public function start() {
    $this->process = $this->processRunner->createFixtureVendorBinProcess([
      'drush',
      'runserver',
      Fixture::WEB_ADDRESS,
    ])
      ->setWorkingDirectory($this->fixture->docrootPath())
      ->setTimeout(NULL)
      ->setIdleTimeout(NULL)
      ->start();
    // Give the process a chance to bootstrap before releasing the thread to
    // code that will depend on it.
    sleep(3);
  }

  /**
   * Stops the web server.
   */
  public function stop() {
    if ($this->process) {
      $this->process->stop();
    }
  }

}
