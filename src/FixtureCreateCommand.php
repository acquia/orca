<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
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
        ->addCode($this->addComposerConfig())
        ->addTask($this->addModuleUnderTest())
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
   *
   * @throws \Robo\Exception\TaskException
   */
  private function createBltProject() {
    return $this->taskComposerCreateProject()
      ->source('acquia/blt-project')
      ->target(self::BUILD_DIR)
      ->interactive(FALSE)
      // Delaying installation to a subsequent step can cut processing time by
      // as much as 30 seconds without changing the outcome.
      ->noInstall();
  }

  /**
   * Adds Composer configuration to the build.
   *
   * @return \Closure
   */
  private function addComposerConfig() {
    return function () {
      $file = new JsonFile(self::BUILD_DIR . '/composer.json');
      $json = new JsonConfigSource($file);
      $json->addRepository('acquia/example', [
        'type' => 'path',
        'url' => '../example',
        'options' => [
          'symlink' => TRUE,
        ],
      ]);
    };
  }

  /**
   * Adds the module under test to the build.
   *
   * @return \Robo\Contract\TaskInterface
   *
   * @throws \Robo\Exception\TaskException
   */
  private function addModuleUnderTest() {
    return $this->taskComposerRequire()
      ->dependency('acquia/example')
      ->workingDir(self::BUILD_DIR);
  }

  /**
   * Commits code changes made to the build directory.
   */
  private function commitCodeChanges() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->taskGitStack()
          ->add('.')
          ->dir(self::BUILD_DIR),
        $this->taskGitStack()
          ->commit('Installed Drupal.')
          ->dir(self::BUILD_DIR),
      ]);
  }

}
