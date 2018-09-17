<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

/**
 * Provides the "tests:run" command.
 */
class TestsRunCommand extends CommandBase {

  /**
   * Runs automated tests.
   *
   * @command tests:run
   * @aliases test
   *
   * @return \Robo\ResultData
   */
  public function execute() {
    return $this->collectionBuilder()
      ->addCode($this->ensureFixture())
      // @todo Run Behat.
      ->addTask($this->runPhpUnitTests())
      ->run();
  }

  /**
   * Ensures the fixture is ready for testing.
   *
   * @return \Closure
   */
  private function ensureFixture() {
    return function () {
      if (!file_exists($this->phpunitConfigFile())) {
        throw new \Exception('The fixture is not ready. Run `orca fixture:create` first.');
      }
    };
  }

  /**
   * Returns the path to the PHPUnit configuration file.
   *
   * @return string
   */
  private function phpunitConfigFile() {
    return $this->buildPath('docroot/core/phpunit.xml.dist');
  }

  /**
   * Runs PHPUnit tests.
   *
   * @return \Robo\Contract\TaskInterface
   */
  private function runPhpUnitTests() {
    return $this->taskPhpUnit()
      ->configFile($this->phpunitConfigFile())
      ->file($this->buildPath('docroot/modules/contrib/acquia'));
  }

}
