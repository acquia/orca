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
        ->addTask($this->addAcquiaProductModules())
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
      ->interactive(FALSE);
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
  private function addAcquiaProductModules() {
    return $this->taskComposerRequire()
      ->dependency([
        'acquia/acsf-tools',
        'acquia/example',
        'drupal/acquia_commercemanager',
        'drupal/acquia_contenthub',
        'drupal/acquia_lift',
        'drupal/acsf',
        'drupal/media_acquiadam',
      ])
      ->workingDir(self::BUILD_DIR);
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
      );
  }

}
