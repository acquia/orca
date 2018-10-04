<?php

namespace Acquia\Orca\Robo\Plugin\Commands;

use Acquia\Orca\Exception\FixtureNotReadyException;
use Acquia\Orca\Task\TaskLoaderTrait;
use Robo\Tasks;

/**
 * Provides a base Robo command implementation.
 */
abstract class CommandBase extends Tasks {

  use TaskLoaderTrait;

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
   * Creates the Drupal database and grants appropriate privileges.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function createDrupalDatabaseAndGrantPrivileges() {
    return $this->taskMysqlExec(
      "CREATE DATABASE IF NOT EXISTS drupal;
      GRANT ALL ON drupal.* TO 'drupal'@'localhost' identified by 'drupal';"
    );
  }

  /**
   * Destroys the test fixture.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function destroyFixture() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->dropDrupalDatabase(),
        $this->fixFilePermissions(),
        $this->taskDeleteDir($this->buildPath()),
      ]);
  }

  /**
   * Drops the Drupal database.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function dropDrupalDatabase() {
    return $this->taskMysqlExec('DROP DATABASE IF EXISTS drupal;');
  }

  /**
   * Empties the Drupal database.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function emptyDrupalDatabase() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->dropDrupalDatabase(),
        $this->createDrupalDatabaseAndGrantPrivileges(),
      ]);
  }

  /**
   * Fixes file permissions on the build directory.
   *
   * Makes writable files the Drupal installer makes read-only, to prevent
   * permission denied errors.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function fixFilePermissions() {
    return $this->taskExec('chmod -R u+w .')
      ->dir($this->buildPath('docroot/sites/default'));
  }

  /**
   * Returns a Drupal installation task.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function installDrupal() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->createDrupalDatabaseAndGrantPrivileges(),
        $this->taskDrushExec(implode(' ', [
          'site-install',
          'minimal',
          "install_configure_form.update_status_module='[FALSE,FALSE]'",
          "install_configure_form.enable_update_status_module=NULL",
          "--site-name=ORCA",
          "--account-name=admin",
          "--account-pass=admin",
          '--no-interaction',
          '-v',
          '--ansi',
        ])),
      ]);
  }

}
