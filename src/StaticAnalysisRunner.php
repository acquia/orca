<?php

namespace Acquia\Orca;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCompatibilitySniffTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TaskFailureException;

/**
 * Runs static analysis tools.
 */
class StaticAnalysisRunner {

  /**
   * The tasks to execute.
   *
   * @var \Acquia\Orca\Task\TaskInterface[]
   */
  private $tasks = [];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\ComposerValidateTask $composer_validate
   *   The Composer validate task.
   * @param \Acquia\Orca\Task\PhpCompatibilitySniffTask $php_compatibility
   *   The PHP compatibility sniff task.
   * @param \Acquia\Orca\Task\PhpLintTask $php_lint
   *   The PHP lint task.
   */
  public function __construct(ComposerValidateTask $composer_validate, PhpCompatibilitySniffTask $php_compatibility, PhpLintTask $php_lint) {
    $this->tasks = [
      $composer_validate,
      $php_lint,
      $php_compatibility,
    ];
  }

  /**
   * Runs the tasks.
   *
   * @param string $path
   *   A filesystem path.
   *
   * @return int
   *   A status code.
   */
  public function run(string $path): int {
    try {
      $status = StatusCodes::OK;
      foreach ($this->tasks as $task) {
        $task->setPath($path)->execute();
      }
    }
    catch (TaskFailureException $e) {
      $status = StatusCodes::ERROR;
    }
    return $status;
  }

}
