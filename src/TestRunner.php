<?php

namespace Acquia\Orca;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Task\BehatTask;
use Acquia\Orca\Task\PhpUnitTask;
use Acquia\Orca\Task\TaskFailureException;
use Symfony\Component\Process\Process;

/**
 * Runs automated tests.
 *
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
class TestRunner {

  /**
   * The tests to perform.
   *
   * @var \Acquia\Orca\Task\TaskInterface[]
   */
  private $tests = [];

  /**
   * The web server process.
   *
   * @var \Symfony\Component\Process\Process
   */
  private $webServerProcess;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\BehatTask $behat
   *   A Behat test.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Task\PhpUnitTask $phpunit
   *   A PHPUnit test.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(BehatTask $behat, Fixture $fixture, PhpUnitTask $phpunit, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->processRunner = $process_runner;
    $this->tests = [$phpunit, $behat];
  }

  /**
   * Runs automated tests.
   *
   * @return int
   */
  public function run(): int {
    try {
      $status = StatusCodes::OK;
      $this->startWebServer();
      foreach ($this->tests as $test) {
        $test->execute();
      }
    }
    catch (TaskFailureException $e) {
      $status = StatusCodes::ERROR;
    }
    finally {
      $this->stopWebServer();
      return $status;
    }
  }

  /**
   * Starts the web server.
   */
  private function startWebServer() {
    $this->webServerProcess = new Process([
      'php',
      '-S',
      Fixture::WEB_ADDRESS,
    ], $this->fixture->docrootPath());
    $this->webServerProcess->start();
  }

  /**
   * Stops the web server.
   */
  private function stopWebServer() {
    $this->webServerProcess->stop();
  }

}
