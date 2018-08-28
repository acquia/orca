<?php

use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Robo\ResultData;
use Robo\Tasks;

// @todo This shouldn't be necessary, but the build command explodes without it.
require './vendor/autoload.php';

class RoboFile extends Tasks {

  private $buildDir = '../build';

  /**
   * Performs an ORCA build.
   */
  public function orcaBuild() {
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
   * Destroys the ORCA build.
   */
  public function orcaDestroy() {
    return $this->_deleteDir($this->buildDir);
  }

  /**
   * Creates a BLT project.
   *
   * @return \Robo\Contract\TaskInterface
   */
  private function createBltProject() {
    return $this->taskComposerCreateProject()
      ->source('acquia/blt-project')
      ->target($this->buildDir)
      ->interactive(FALSE);
  }

  /**
   * Adds Composer configuration to the build.
   *
   * @return \Closure
   */
  private function addComposerConfig() {
    return function () {
      $file = new JsonFile("{$this->buildDir}/composer.json");
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
      ->workingDir($this->buildDir);
  }

}
