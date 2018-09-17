<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use AcquiaOrca\Exception\FixtureNotReadyException;
use Robo\Tasks;

/**
 * Provides a base Robo command implementation.
 */
abstract class CommandBase extends Tasks {

  /**
   * The base fixture Git branch.
   */
  const BASE_FIXTURE_BRANCH = 'base-fixture';

  /**
   * Executes the command.
   *
   * @return \Robo\Result|int
   */
  abstract public function execute();

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
   *
   * @throws \AcquiaOrca\Exception\FixtureNotReadyException
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

  /**
   * Gets the list of Acquia product module Composer package strings.
   *
   * @return string[]
   *   An indexed array of package strings, including constraints, e.g.,
   *   "drupal/example:^1.0".
   */
  protected function getAcquiaProductModulePackageStrings() {
    return require __DIR__ . '/../../config/product-modules.inc.php';
  }

}
