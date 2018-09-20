<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use AcquiaOrca\Exception\FixtureNotReadyException;

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
   * @return \Robo\Result|int
   */
  public function execute() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->runPhpUnit(),
        $this->runBehat(),
      ])
      ->run();
  }

  /**
   * Runs PHPUnit tests.
   *
   * @return \Robo\Task\Testing\PHPUnit
   *
   * @throws \AcquiaOrca\Exception\FixtureNotReadyException
   */
  private function runPhpUnit() {
    $phpunit_config_file = $this->buildPath('docroot/core/phpunit.xml.dist');
    if (!file_exists($phpunit_config_file)) {
      throw new FixtureNotReadyException();
    }

    return $this->taskPhpUnit()
      ->configFile($phpunit_config_file)
      ->file($this->buildPath('docroot/modules/contrib/acquia'));
  }

  /**
   * Executes Behat stories.
   *
   * @return \Robo\Task\Testing\Behat
   */
  private function runBehat() {
    return $this->taskBehat();
  }

}
