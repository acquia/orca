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
   * Executes a BLT command.
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
   * Creates the Drupal database and grants appropriate privileges.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function taskCreateDrupalDatabaseAndGrantPrivileges() {
    return $this->taskMysqlExec(
      "CREATE DATABASE IF NOT EXISTS drupal;
      GRANT ALL ON drupal.* TO 'drupal'@'localhost' identified by 'drupal';"
    );
  }

  /**
   * Drops the Drupal database.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function taskDropDrupalDatabase() {
    return $this->taskMysqlExec('DROP DATABASE IF EXISTS drupal;');
  }

  /**
   * Empties the Drupal database.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function taskEmptyDrupalDatabase() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->taskDropDrupalDatabase(),
        $this->taskCreateDrupalDatabaseAndGrantPrivileges(),
      ]);
  }

  /**
   * Returns a Drupal installation task.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function taskInstallDrupal() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->taskCreateDrupalDatabaseAndGrantPrivileges(),
        $this->taskBltExec('drupal:install -n'),
      ]);
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
   * Executes a Drush command.
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
   * Fixes file permissions on the build directory.
   *
   * Makes writable files the Drupal installer makes read-only, to prevent
   * permission denied errors.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function taskFixFilePermissions() {
    return $this->taskExec('chmod -R u+w .')
      ->dir($this->buildPath('docroot/sites/default'));
  }

}
