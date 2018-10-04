<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Exception\FixtureNotReadyException;
use Robo\Task\Base\loadTasks as BaseTasksLoader;
use Robo\TaskAccessor;

/**
 * Provides task loader methods.
 */
trait TaskLoaderTrait {

  use BaseTasksLoader;
  use TaskAccessor;

  /**
   * Executes a Drush command.
   *
   * @param string $command
   *   The command string to execute, including options and arguments.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function taskDrushExec($command) {
    return $this->taskScriptExec('vendor/bin/drush', "@self {$command}");
  }

  /**
   * Executes a MySQL command.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function taskMysqlExec($command) {
    return $this->taskExec(sprintf('mysql -uroot -e "%s"', $command));
  }

  /**
   * Returns a task that does nothing.
   *
   * @return \Acquia\Orca\Task\NullTask
   */
  protected function taskNull() {
    /** @var \Acquia\Orca\Task\NullTask $task */
    $task = $this->task(NullTask::class);
    return $task;
  }

  /**
   * Executes a script command.
   *
   * @param string $path
   *   The path to the script relative to the build directory (no leading slash
   *   (/)).
   * @param string $command
   *   The command string to execute, including options and arguments.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function taskScriptExec($path, $command) {
    return $this->collectionBuilder()
      ->addCode(function () use ($path, $command) {
        $path = $this->buildPath($path);

        if (!file_exists($path)) {
          throw new FixtureNotReadyException();
        }

        return $this->taskExec("${path} {$command}")
          ->dir($this->buildPath())
          ->run();
      });
  }

}
