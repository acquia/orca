<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\ResultData;

/**
 * Provides the "test" command.
 */
class TestCommand extends CommandBase {

  /**
   * Performs tests
   *
   * @command test
   * @option build-directory The path to the build directory, absolute or
   *   relative to the current working directory.
   *
   * @param array $opts
   *
   * @return \Robo\ResultData
   */
  public function execute($opts = ['build-directory|d' => '../build']) {
    $this->commandOptions = $opts;
    try {
      $this->collectionBuilder()
        ->addCode($this->ensureBuild())
        // @todo Run Behat.
        ->addTask($this->runPhpUnitTests())
        ->run();
    } catch (\Exception $e) {
      return new ResultData(ResultData::EXITCODE_ERROR, $e->getMessage());
    }
    return new ResultData(ResultData::EXITCODE_OK, 'Testing complete.');
  }

  /**
   * Ensures the build is ready for testing.
   *
   * @return \Closure
   */
  private function ensureBuild() {
    return function () {
      if (!file_exists($this->getPhpUnitConfigFile())) {
        throw new \Exception('The build is not ready. Run `orca build` first.');
      }
    };
  }

  /**
   * Gets the path to the PHPUnit configuration file.
   *
   * @return string
   */
  private function getPhpUnitConfigFile() {
    return $this->getBuildDir() . '/docroot/core/phpunit.xml.dist';
  }

  /**
   * Runs PHPUnit tests.
   *
   * @return \Robo\Contract\TaskInterface
   */
  private function runPhpUnitTests() {
    return $this->taskPhpUnit()
      ->configFile($this->getPhpUnitConfigFile())
      ->file($this->getBuildDir() . '/docroot/modules/contrib/example/src');
  }

}
