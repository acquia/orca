<?php

namespace Acquia\Orca\Tasks;

/**
 * Provides an interface for defining a task.
 */
interface TaskInterface {

  /**
   * Executes the test.
   *
   * @throws \Acquia\Orca\Tasks\TaskFailureException
   */
  public function execute(): void;

  /**
   * Sets the path.
   *
   * @param string $path
   *   A filesystem path.
   *
   * @return self
   */
  public function setPath(string $path): TaskInterface;

}
