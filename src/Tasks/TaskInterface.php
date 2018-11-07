<?php

namespace Acquia\Orca\Tasks;

/**
 * Provides an interface for defining an automated test.
 */
interface TaskInterface {

  /**
   * Executes the test.
   *
   * @throws \Acquia\Orca\Tasks\TaskFailureException
   */
  public function execute(): void;

}
