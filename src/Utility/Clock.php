<?php

namespace Acquia\Orca\Utility;

/**
 * Provides time related functions.
 */
class Clock {

  /**
   * Delays the program execution for the given number of seconds.
   *
   * @param int $seconds
   *   Halt time in seconds.
   */
  public function sleep(int $seconds): void {
    sleep($seconds);
  }

}
