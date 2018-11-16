<?php

namespace Acquia\Orca;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\ProductData;
use Acquia\Orca\Tasks\BehatTask;
use Acquia\Orca\Tasks\PhpUnitTask;
use Acquia\Orca\Tasks\TaskFailureException;
use Symfony\Component\Process\Process;

/**
 * Runs automated tests.
 *
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\ProcessRunner $processRunner
 * @property \Acquia\Orca\Fixture\ProductData $productData
 */
class TestRunner {

  /**
   * The tests to perform.
   *
   * @var \Acquia\Orca\Tasks\TaskInterface[]
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
   * @param \Acquia\Orca\Tasks\BehatTask $behat
   *   A Behat test.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Tasks\PhpUnitTask $phpunit
   *   A PHPUnit test.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\ProductData $product_data
   *   The product data.
   */
  public function __construct(BehatTask $behat, Fixture $fixture, PhpUnitTask $phpunit, ProcessRunner $process_runner, ProductData $product_data) {
    $this->fixture = $fixture;
    $this->processRunner = $process_runner;
    $this->productData = $product_data;
    $this->tests = [$phpunit, $behat];
  }

  /**
   * Runs automated tests.
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
