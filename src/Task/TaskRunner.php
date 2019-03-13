<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\TaskFailureException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs tasks.
 */
class TaskRunner {

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

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
   * Constructs an instance.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   */
  public function __construct(SymfonyStyle $output) {
    $this->output = $output;
  }

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
   * @return self
   *   The task runner.
   */
  public function addTask(TaskInterface $task): self {
    $this->tasks[] = $task;
    return $this;
  }

  /**
   * Runs the tasks.
   *
   * @return int
   *   The last task's exit status code.
   */
  public function run(): int {
    try {
      foreach ($this->tasks as $task) {
        $this->output->section($task->statusMessage());
        $task->setPath($this->path)->execute();
      }
    }
    catch (TaskFailureException $e) {
      return StatusCodes::ERROR;
    }
    return StatusCodes::OK;
  }

  /**
   * Sets the path to pass to the tasks.
   *
   * @param string $path
   *   Any valid filesystem path, absolute or relative.
   *
   * @return self
   *   The task runner.
   */
  public function setPath(string $path): self {
    $this->path = $path;
    return $this;
  }

}
