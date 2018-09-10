<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Boedah\Robo\Task\Drush\DrushStack;
use Boedah\Robo\Task\Drush\loadTasks as DrushTasks;
use Robo\Exception\TaskException;
use Robo\Tasks;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Provides a base Robo command implementation.
 *
 * All Composer tasks are overridden here to specify the global Composer path so
 * as to avoid version issues since Composer itself is a required dependency.
 */
abstract class CommandBase extends Tasks {

  use DrushTasks;

  /**
   * The relative path to the build directory.
   */
  const BUILD_DIR = '../build';

  /**
   * Executes the command.
   *
   * @return \Robo\ResultData
   */
  abstract public function execute();

  /**
   * Installs Drupal.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function installDrupal() {
    return $this->taskExec(CommandBase::BUILD_DIR . '/vendor/bin/blt drupal:install -n');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerConfig($pathToComposer = NULL) {
    return parent::taskComposerConfig($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerCreateProject($pathToComposer = NULL) {
    return parent::taskComposerCreateProject($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerDumpAutoload($pathToComposer = NULL) {
    return parent::taskComposerDumpAutoload($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerInit($pathToComposer = NULL) {
    return parent::taskComposerInit($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerInstall($pathToComposer = NULL) {
    return parent::taskComposerInstall($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerRemove($pathToComposer = NULL) {
    return parent::taskComposerRemove($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerRequire($pathToComposer = NULL) {
    return parent::taskComposerRequire($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerUpdate($pathToComposer = NULL) {
    return parent::taskComposerUpdate($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Robo\Exception\TaskException
   */
  protected function taskComposerValidate($pathToComposer = NULL) {
    return parent::taskComposerValidate($this->handleComposerPathArg($pathToComposer));
  }

  /**
   * {@inheritdoc}
   */
  protected function taskDrushStack($pathToDrush = NULL) {
    $pathToDrush = $pathToDrush ?: self::BUILD_DIR . '/vendor/bin/drush';
    /** @var \Boedah\Robo\Task\Drush\DrushStack $task */
    $task = $this->task(DrushStack::class, $pathToDrush);
    return $task->drupalRootDirectory(realpath(self::BUILD_DIR . '/docroot'));
  }

  /**
   * Handles the Composer path argument, defaulting to the global install.
   *
   * @param string|null $path_to_composer
   *   A path string argument or NULL.
   *
   * @return string
   *
   * @throws \Robo\Exception\TaskException
   */
  private function handleComposerPathArg($path_to_composer) {
    return $path_to_composer ?: $this->getPathToGlobalComposer();
  }

  /**
   * Gets the path to the global Composer installation.
   *
   * @return string
   *
   * @throws \Robo\Exception\TaskException
   */
  private function getPathToGlobalComposer() {
    static $path;
    if (!$path) {
      $finder = new ExecutableFinder();
      $path = $finder->find('composer');
      if (!$path) {
        throw new TaskException(__CLASS__, 'Global Composer installation could be found.');
      }
    }
    return $path;
  }

}
