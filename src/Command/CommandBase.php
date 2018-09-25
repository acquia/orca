<?php

namespace Acquia\Orca\Robo\Plugin\Commands;

use Acquia\Orca\Exception\FixtureNotReadyException;
use Robo\Tasks;

/**
 * Provides a base Robo command implementation.
 */
abstract class CommandBase extends Tasks {

  /**
   * The directory that all Acquia product modules are grouped under.
   *
   * Relative to the build path.
   */
  const ACQUIA_PRODUCT_MODULES_DIR = 'docroot/modules/contrib/acquia';

  /**
   * The base fixture Git branch.
   */
  const BASE_FIXTURE_BRANCH = 'base-fixture';

  /**
   * Executes the command.
   */
  abstract public function execute();

  /**
   * Asserts that the test fixture is ready.
   */
  protected function assertFixtureIsReady() {
    if (!file_exists($this->buildPath(self::ACQUIA_PRODUCT_MODULES_DIR))) {
      throw new FixtureNotReadyException();
    }
  }

  /**
   * Returns a Drupal installation task.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function installDrupal() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->taskMysqlExec("CREATE DATABASE IF NOT EXISTS drupal; GRANT ALL ON drupal.* TO 'drupal'@'localhost' identified by 'drupal';"),
        $this->taskBltExec('drupal:install -n'),
      ]);
  }

  /**
   * Returns a BLT task.
   *
   * @param string $command
   *   The command string to execute, including options and arguments.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function taskBltExec($command) {
    return $this->taskScriptExec('vendor/bin/blt', $command);
  }

  /**
   * Returns a MySQL task.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function taskMysqlExec($command) {
    return $this->taskExec(sprintf('mysql -uroot -e "%s"', $command));
  }

  /**
   * Returns a script task.
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

  /**
   * Gets the build path with an optional sub-path appended.
   *
   * @param string $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   */
  protected function buildPath($sub_path = '') {
    $path = realpath('..') . '/build';
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }
    return $path;
  }

  /**
   * Returns a Drush stack task.
   *
   * @param string $command
   *   The command string to execute, including options and arguments.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function taskDrushExec($command) {
    return $this->taskScriptExec('vendor/bin/drush', $command);
  }

}
