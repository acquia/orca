<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Exception\TaskFailureException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs tasks.
 */
class TaskRunner {

  /**
   * A list of test failure descriptions.
   *
   * @var string[]
   */
  private $failures = [];

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
    $status = StatusCode::OK;
    foreach ($this->tasks as $task) {
      try {
        $this->output->section($task->statusMessage());
        $task->setPath($this->path)->execute();
      }
      catch (TaskFailureException $e) {
        $failure = $task->label();
        $this->output->block($failure, 'FAILURE', 'fg=white;bg=red');
        $this->failures[] = $failure;
        $status = StatusCode::ERROR;
      }
    }

    if ($this->failures) {
      $this->output->block(implode(PHP_EOL, $this->failures), 'FAILURES', 'fg=white;bg=red', ' ', TRUE);
      $this->output->writeln('');
      return StatusCode::ERROR;
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
   *   The task runner.
   */
  public function setPath(string $path): self {
    $this->path = $path;
    return $this;
  }

}
