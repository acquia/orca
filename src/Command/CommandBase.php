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
   * @return \Robo\ResultData
   */
  abstract public function execute();

  /**
   * Installs Drupal.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  protected function installDrupal() {
    return $this->taskBltExec('drupal:install -n');
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
          throw new FixtureNotReadyException($this->io());
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
