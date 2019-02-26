<?php

namespace Acquia\Orca\Task;

/**
 * Provides an interface for defining tasks.
 */
interface TaskInterface {

  /**
   * Executes the test.
   *
   * @throws \Acquia\Orca\Exception\TaskFailureException
   */
  public function execute(): void;

  /**
   * Sets the path.
   *
   * @param string $path
   *   A filesystem path.
   *
   * @return self
   *   The task object.
   */
  public function setPath(string $path): TaskInterface;

  /**
   * Returns a status message describing the task being performed.
   *
   * @return string
   *   A status message, e.g., "Performing task".
   */
  public function statusMessage(): string;

}
