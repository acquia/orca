<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\ResultData;

/**
 * Provides the "test" command.
 */
class TestCommand extends CommandBase {

  /**
   * The path to the PHPUnit configuration file.
   */
  const PHPUNIT_CONFIG_FILE = self::BUILD_DIR . '/docroot/core/phpunit.xml.dist';

  /**
   * Performs tests
   *
   * @command test
   */
  public function execute() {
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
      if (!file_exists(self::PHPUNIT_CONFIG_FILE)) {
        throw new \Exception('The build is not ready. Run `orca build` first.');
      }
    };
  }

  /**
   * Runs PHPUnit tests.
   *
   * @return \Robo\Contract\TaskInterface
   * @throws \Robo\Exception\TaskException
   */
  private function runPhpUnitTests() {
    return $this->taskPhpUnit()
      ->configFile(self::PHPUNIT_CONFIG_FILE)
      ->file(self::BUILD_DIR . '/docroot/modules/contrib/example/src');
  }

}
