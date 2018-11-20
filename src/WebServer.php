<?php

namespace Acquia\Orca;

use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Process\Process;

/**
 * Provides a fixture.
 *
 * @property \Acquia\Orca\Fixture\Fixture $fixture
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
   */
  public function __construct(Fixture $fixture) {
    $this->fixture = $fixture;
  }

  /**
   * Starts the web server.
   */
  public function start() {
    $this->process = new Process([
      'php',
      '-S',
      Fixture::WEB_ADDRESS,
    ], $this->fixture->docrootPath());
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
