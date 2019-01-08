<?php

namespace Acquia\Orca\Server;

use Acquia\Orca\Utility\Clock;

/**
 * Provides a single interface to all servers.
 */
class ServerStack implements ServerInterface {

  /**
   * The clock.
   *
   * @var \Acquia\Orca\Utility\Clock
   */
  private $clock;

  /**
   * The servers.
   *
   * @var \Acquia\Orca\Server\ServerInterface[]
   */
  private $servers;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Server\ChromeDriverServer $chrome_driver_server
   *   The ChromeDriver server.
   * @param \Acquia\Orca\Utility\Clock $clock
   *   The clock.
   * @param \Acquia\Orca\Server\WebServer $web_server
   *   The web server.
   */
  public function __construct(ChromeDriverServer $chrome_driver_server, Clock $clock, WebServer $web_server) {
    $this->clock = $clock;
    $this->servers = [
      $chrome_driver_server,
      $web_server,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    foreach ($this->servers as $server) {
      $server->start();
    }

    // Give the servers a chance to start up before releasing the thread to
    // tasks that will depend on them.
    $this->clock->sleep(3);
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    foreach ($this->servers as $server) {
      $server->stop();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function wait(): void {
    foreach ($this->servers as $server) {
      $server->wait();
    }
  }

}
