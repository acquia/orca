<?php

namespace Acquia\Orca;

use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Process\Process;

/**
 * Provides a fixture.
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
    ]);
    $this->process->setWorkingDirectory($this->fixture->docrootPath());
    $this->process->start();
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
