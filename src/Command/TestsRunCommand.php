<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Symfony\Component\Finder\Finder;

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
    $this->assertFixtureIsReady();

    return $this->collectionBuilder()
      ->addTaskList([
        // @todo Re-add PHPUnit.
        // $this->runPhpUnit(),
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
    return $this->taskPhpUnit()
      ->configFile($this->buildPath('docroot/core/phpunit.xml.dist'))
      ->file($this->buildPath('docroot/modules/contrib/acquia'));
  }

  /**
   * Executes Behat stories.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function runBehat() {
    $tasks = [];
    /** @var \SplFileObject $file */
    foreach ($this->getBehatConfigFiles() as $file) {
      $tasks[] = $this->taskBehat()
        ->dir($file->getPath())
        ->config($file->getPathname());
    }
    return $this->collectionBuilder()
      ->addTaskList($tasks);
  }

  /**
   * Finds all Behat config files in the Acquia product module directories.
   *
   * @return \Symfony\Component\Finder\Finder
   */
  private function getBehatConfigFiles() {
    return Finder::create()
      ->files()
      ->followLinks()
      ->in($this->buildPath('docroot/modules/contrib/acquia'))
      ->name('behat.yml');
  }

}
