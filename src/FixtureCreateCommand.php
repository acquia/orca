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
        ->addTask($this->commitCodeChanges())
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
   */
  private function commitCodeChanges() {
    return $this->collectionBuilder()
      ->addTask(
        $this->taskGitStack()
          ->dir(self::BUILD_DIR)
          ->silent(TRUE)
          ->add('.')
          ->commit('Installed Drupal.')
          ->exec('branch -f ' . self::BASE_FIXTURE_BRANCH)
      );
  }

}
