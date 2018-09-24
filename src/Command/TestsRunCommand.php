<?php

namespace Acquia\Orca\Robo\Plugin\Commands;

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
   *
   * @throws \Acquia\Orca\Exception\FixtureNotReadyException
   */
  public function execute() {
    $this->assertFixtureIsReady();

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
   * @return \Robo\Collection\CollectionBuilder
   */
  private function runPhpUnit() {
    return $this->runFramework('taskPhpUnit', 'phpunit.xml.dist', 'configFile');
  }

  /**
   * Executes Behat stories.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function runBehat() {
    return $this->runFramework('taskBehat', 'behat.yml', 'config');
  }

  /**
   * Runs a test framework.
   *
   * @param string $runner_method
   *   The method name for getting a Robo task for the test framework.
   * @param string $config_filename
   *   The name pattern of the framework config files.
   * @param string $config_file_method
   *   The method name to call with the config filename.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function runFramework($runner_method, $config_filename, $config_file_method) {
    $tasks = [];
    /** @var \SplFileObject $file */
    foreach ($this->getConfigFiles($config_filename) as $file) {
      /** @var \Robo\Contract\TaskInterface|\Robo\Task\Testing\Behat|\Robo\Task\Testing\PHPUnit $runner */
      $runner = call_user_func_array([$this, $runner_method], []);
      $tasks[] = $runner->dir($file->getPath())
        ->{$config_file_method}($file->getPathname());
    }
    return $this->collectionBuilder()
      ->addTaskList($tasks);
  }

  /**
   * Finds all files with a given name in the Acquia product module directories.
   *
   * @param string $filename
   *   The name pattern of the config files to find.
   *
   * @return \Symfony\Component\Finder\Finder
   */
  private function getConfigFiles($filename) {
    return Finder::create()
      ->files()
      ->followLinks()
      ->in($this->buildPath(self::ACQUIA_PRODUCT_MODULES_DIR))
      ->notPath('vendor')
      ->name($filename);
  }

}
