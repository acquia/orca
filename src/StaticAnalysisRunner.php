<?php

namespace Acquia\Orca;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Tasks\ComposerValidateTask;
use Acquia\Orca\Tasks\PhpCompatibilitySniffTask;
use Acquia\Orca\Tasks\PhpLintTask;
use Acquia\Orca\Tasks\TaskFailureException;

/**
 * Runs static analysis tools.
 */
class StaticAnalysisRunner {

  /**
   * The tasks to execute.
   *
   * @var \Acquia\Orca\Tasks\TaskInterface[]
   */
  private $tasks = [];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Tasks\ComposerValidateTask $composer_validate
   *   The Composer validate task.
   * @param \Acquia\Orca\Tasks\PhpCompatibilitySniffTask $php_compatibility
   *   The PHP compatibility sniff task.
   * @param \Acquia\Orca\Tasks\PhpLintTask $php_lint
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
