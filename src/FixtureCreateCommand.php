<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\ResultData;

/**
 * Provides the "fixture:create" command.
 */
class FixtureCreateCommand extends CommandBase {

  /**
   * Creates the base test fixture.
   *
   * Creates a BLT-based Drupal site build, includes the module under test
   * using Composer, installs Drupal, and commits any code changes.
   *
   * @command fixture:create
   * @aliases build
   *
   * @return \Robo\ResultData
   */
  public function execute() {
    try {
      $result = $this->collectionBuilder()
        ->addTask($this->createBltProject())
        ->addTask($this->installDrupal())
        ->addTask($this->commitCodeChanges('Installed Drupal.'))
        ->addTask($this->addAcquiaProductModules())
        ->addTask($this->commitCodeChanges('Added Acquia product modules.', self::BASE_FIXTURE_BRANCH))
        ->run();
    }
    catch (\Exception $e) {
      return new ResultData(ResultData::EXITCODE_ERROR, $e->getMessage());
    }
    return $result;
  }

  /**
   * Creates a BLT project.
   *
   * @return \Robo\Contract\TaskInterface
   */
  private function createBltProject() {
    return $this->taskComposerCreateProject()
      ->source('acquia/blt-project')
      ->target(self::BUILD_DIR)
      ->interactive(FALSE);
  }

  /**
   * Commits code changes made to the build directory.
   *
   * @param string $message
   *   The commit message to use.
   * @param string|false $backup_branch
   *   The name of a branch to point at the commit for backup or FALSE to skip
   *   creating one.
   *
   * @return \Robo\Task\Vcs\GitStack
   */
  private function commitCodeChanges($message, $backup_branch = FALSE) {
    $task = $this->taskGitStack()
      ->dir(self::BUILD_DIR)
      ->silent(TRUE)
      ->add('.')
      ->commit($message, '--allow-empty');
    if ($backup_branch) {
      $task->exec("branch -f {$backup_branch}");
    }
    return $task;
  }

  /**
   * Adds Acquia product modules to the codebase.
   *
   * @return \Robo\Contract\TaskInterface
   */
  private function addAcquiaProductModules() {
    return $this->taskComposerRequire()
      ->dependency(require __DIR__ . '/../config/product-modules.inc.php')
      ->dir(self::BUILD_DIR);
  }

}
