<?php

namespace Acquia\Orca\Domain\Tool;

/**
 * Provides an interface for defining tasks.
 */
interface TaskInterface {

  /**
   * Executes the task.
   *
   * @throws \Acquia\Orca\Exception\OrcaTaskFailureException
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
   * Gets the human-readable task label.
   *
   * @return string
   *   The task name, e.g., "Example Task".
   */
  public function label(): string;

  /**
   * Returns a status message describing the task being performed.
   *
   * @return string
   *   A status message, e.g., "Performing task".
   */
  public function statusMessage(): string;

}
