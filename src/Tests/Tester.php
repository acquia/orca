<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\IoTrait;
use Acquia\Orca\ProcessRunnerTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Runs automated tests.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 */
class Tester {

  use IoTrait;
  use ProcessRunnerTrait;

  private const WEB_ADDRESS = 'localhost:8000';

  /**
   * The web server process.
   *
   * @var \Symfony\Component\Process\Process
   */
  private $webServerProcess;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Facade $facade
   *   The fixture.
   */
  public function __construct(Facade $facade) {
    $this->facade = $facade;
  }

  /**
   * Runs automated tests.
   */
  public function test() {
    $this->startWebServer();
    $this->runPhpUnitTests();
    $this->runBehatStories();
    $this->stopWebServer();
  }

  /**
   * Starts the web server.
   */
  private function startWebServer() {
    $this->webServerProcess = new Process([
      'php',
      '-S',
      self::WEB_ADDRESS,
    ], $this->facade->docrootPath());
    $this->webServerProcess->start();
  }

  /**
   * Runs PHPUnit tests.
   */
  private function runPhpUnitTests() {
    $this->ensurePhpUnitConfig();

    $this->runVendorBinProcess([
      'phpunit',
      "--configuration={$this->facade->docrootPath('core/phpunit.xml.dist')}",
      "--bootstrap={$this->facade->docrootPath('core/tests/bootstrap.php')}",
      $this->facade->productModuleInstallPath(),
    ]);
  }

  /**
   * Ensures that PHPUnit is properly configured.
   */
  private function ensurePhpUnitConfig() {
    $path = $this->facade->docrootPath('core/phpunit.xml.dist');
    $doc = new \DOMDocument();
    $doc->load($path);
    $xpath = new \DOMXPath($doc);

    $xpath->query('//phpunit/php/env[@name="SIMPLETEST_BASE_URL"]')
      ->item(0)
      ->setAttribute('value', sprintf('http://%s', self::WEB_ADDRESS));
    $xpath->query('//phpunit/php/env[@name="SIMPLETEST_DB"]')
      ->item(0)
      ->setAttribute('value', 'sqlite://localhost/sites/default/files/.ht.sqlite');

    $doc->save($path);
  }

  /**
   * Runs Behat stories.
   */
  private function runBehatStories() {
    /** @var \Symfony\Component\Finder\SplFileInfo $config_file */
    foreach ($this->getBehatConfigFiles() as $config_file) {
      $this->runVendorBinProcess([
        'behat',
        "--config={$config_file->getPathname()}",
      ]);
    }
  }

  /**
   * Finds all Behat config files.
   *
   * @return \Symfony\Component\Finder\Finder
   */
  private function getBehatConfigFiles() {
    return Finder::create()
      ->files()
      ->followLinks()
      ->in($this->facade->productModuleInstallPath())
      ->notPath('vendor')
      ->name('behat.yml');
  }

  /**
   * Stops the web server.
   */
  private function stopWebServer() {
    $this->webServerProcess->stop();
  }

}
