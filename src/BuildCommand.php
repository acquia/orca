<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Robo\ResultData;

/**
 * Provides the "build" command.
 */
class BuildCommand extends CommandBase {

  /**
   * Performs a build
   *
   * @command build
   */
  public function execute() {
    try {
      $this->collectionBuilder()
        ->addTask($this->createBltProject())
        ->addCode($this->addComposerConfig())
        ->addTask($this->addModuleUnderTest())
        ->run();
    } catch (\Exception $e) {
      return new ResultData(ResultData::EXITCODE_ERROR, $e->getMessage());
    }
    return new ResultData(ResultData::EXITCODE_OK, 'Build complete.');
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
   */
  private function addModuleUnderTest() {
    return $this->taskComposerRequire()
      ->dependency('acquia/example')
      ->workingDir(self::BUILD_DIR);
  }

}
