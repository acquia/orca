<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Command\StatusCodes;

/**
 * Runs tasks.
 */
class TaskRunner {

  /**
   * A path to pass to the tasks.
   *
   * @var string|null
   */
  private $path;

  /**
   * The tasks to run.
   *
   * @var \Acquia\Orca\Task\TaskInterface[]
   */
  private $tasks = [];

  /**
   * Resets the task list on clone.
   */
  public function __clone() {
    $this->tasks = [];
  }

  /**
   * Adds a task to be run.
   *
   * @param \Acquia\Orca\Task\TaskInterface $task
   *   The task to add.
   *
   * @return \Acquia\Orca\Task\TaskRunner
   */
  public function addTask(TaskInterface $task): self {
    $this->tasks[] = $task;
    return $this;
  }

  /**
   * Runs the tasks.
   *
   * @return int
   */
  public function run(): int {
    try {
      $status = StatusCodes::OK;
      foreach ($this->tasks as $task) {
        $task->setPath($this->path)->execute();
      }
    }
    catch (TaskFailureException $e) {
      $status = StatusCodes::ERROR;
    }
    return $status;
  }

  /**
   * Sets the path to pass to the tasks.
   *
   * @param string $path
   *   Any valid filesystem path, absolute or relative.
   *
   * @return self
   */
  public function setPath(string $path): self {
    $this->path = $path;
    return $this;
  }

}
