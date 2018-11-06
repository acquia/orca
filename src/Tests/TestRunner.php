<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Fixture\ProductData;
use Acquia\Orca\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Runs automated tests.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 * @property \Acquia\Orca\ProcessRunner $processRunner
 * @property \Acquia\Orca\Fixture\ProductData $productData
 */
class TestRunner {

  /**
   * The tests to perform.
   *
   * @var \Acquia\Orca\Tests\TestInterface[]
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
   * @param \Acquia\Orca\Tests\Behat $behat
   *   A Behat test.
   * @param \Acquia\Orca\Fixture\Facade $facade
   *   The fixture.
   * @param \Acquia\Orca\Tests\PhpUnit $phpunit
   *   A PHPUnit test.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\ProductData $product_data
   *   The product data.
   */
  public function __construct(Behat $behat, Facade $facade, PhpUnit $phpunit, ProcessRunner $process_runner, ProductData $product_data) {
    $this->facade = $facade;
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
    catch (TestFailureException $e) {
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
      Facade::WEB_ADDRESS,
    ], $this->facade->docrootPath());
    $this->webServerProcess->start();
  }

  /**
   * Stops the web server.
   */
  private function stopWebServer() {
    $this->webServerProcess->stop();
  }

}
