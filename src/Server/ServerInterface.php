<?php

namespace Acquia\Orca\Server;

/**
 * Provides an interface for defining servers.
 */
interface ServerInterface {

  /**
   * Starts the server.
   */
  public function start(): void;

  /**
   * Stops the server.
   */
  public function stop(): void;

  /**
   * Halts the server until the process is completed.
   */
  public function wait(): void;

}
