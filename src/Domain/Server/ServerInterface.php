<?php

namespace Acquia\Orca\Domain\Server;

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

  /**
   * Gets the process details.
   *
   * @return array
   *   The process or array of processes.
   */
  public function getProcessDetails(): array;

}
