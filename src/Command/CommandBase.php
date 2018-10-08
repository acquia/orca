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
   * Throws an exception if a given assertion loosely evaluates to FALSE.
   *
   * @code
   * $this->assert(rand() % 5, "Random error.");
   * @endcode
   *
   * @param mixed $assertion
   *   The assertion.
   * @param string $description
   *   An optional description that will be included in the failure message if
   *   the assertion fails.
   */
  protected function assert($assertion, $description = '') {
    if (!$assertion) {
      throw new \RuntimeException($description);
    }
  }

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
   * Makes writable those files the Drupal installer makes read-only, to prevent
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
          'install_configure_form.enable_update_status_module=NULL',
          '--db-url=sqlite://sites/default/files/.ht.sqlite',
          '--site-name=ORCA',
          '--account-name=admin',
          '--account-pass=admin',
          '--no-interaction',
          '--verbose',
          '--ansi',
        ])),
        $this->createPhpUnitConfigurationFile(),
      ])
      ->addCode($this->configurePhpUnitDatabaseUrl('sqlite://localhost/sites/default/files/.ht.sqlite'));
  }

  /**
   * Sets the SIMPLETEST_DB environment variable in phpunit.xml.
   *
   * @param string $db_url
   *   The Drupal database URL.
   *
   * @return \Closure
   */
  protected function configurePhpUnitDatabaseUrl($db_url) {
    return function () use ($db_url) {
      $path = $this->buildPath('docroot/core/phpunit.xml');

      $doc = new \DOMDocument();
      $doc->load($path);

      $xpath = new \DOMXPath($doc);
      $node = $xpath->query('//phpunit/php/env[@name="SIMPLETEST_DB"]')->item(0);
      $node->setAttribute('value', $db_url);
      $doc->save($path);
    };
  }

  /**
   * Creates a clean phpunit.xml configuration file.
   *
   * This copies Drupal core's phpunit.xml.dist to phpunit.xml.
   *
   * @param bool $overwrite
   *   If TRUE, any existing phpunit.xml in the Drupal core directory will be
   *   overwritten.
   *
   * @return \Robo\Task\Filesystem\FilesystemStack
   */
  protected function createPhpUnitConfigurationFile($overwrite = TRUE) {
    return $this->taskFilesystemStack()
      ->copy(
        $this->buildPath('docroot/core/phpunit.xml.dist'),
        $this->buildPath('docroot/core/phpunit.xml'),
        $overwrite
      );
  }

}
