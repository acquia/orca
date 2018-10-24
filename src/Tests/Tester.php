<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Fixture\ProductData;
use Acquia\Orca\IoTrait;
use Acquia\Orca\ProcessRunnerTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Runs automated tests.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 * @property \Acquia\Orca\Fixture\ProductData $productData
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
   * @param \Acquia\Orca\Fixture\ProductData $product_data
   *   The product data.
   */
  public function __construct(Facade $facade, ProductData $product_data) {
    $this->facade = $facade;
    $this->productData = $product_data;
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
      $this->testsDirectory(),
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

    $this->setSimpletestSettings($path, $doc, $xpath);
    $this->disableSymfonyDeprecationsHelper($path, $doc, $xpath);
    $this->setMinkDriverArguments($path, $doc, $xpath);
  }

  /**
   * Sets Simpletest settings.
   *
   * @param string $path
   *   The path.
   * @param \DOMDocument $doc
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   The XPath object.
   */
  private function setSimpletestSettings(string $path, \DOMDocument $doc, \DOMXPath $xpath): void {
    $xpath->query('//phpunit/php/env[@name="SIMPLETEST_BASE_URL"]')
      ->item(0)
      ->setAttribute('value', sprintf('http://%s', self::WEB_ADDRESS));
    $xpath->query('//phpunit/php/env[@name="SIMPLETEST_DB"]')
      ->item(0)
      ->setAttribute('value', 'sqlite://localhost/sites/default/files/.ht.sqlite');
    $doc->save($path);
  }

  /**
   * Disables the Symfony Deprecations Helper.
   *
   * @param string $path
   *   The path.
   * @param \DOMDocument $doc
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   The XPath object.
   */
  private function disableSymfonyDeprecationsHelper(string $path, \DOMDocument $doc, \DOMXPath $xpath): void {
    if (!$xpath->query('//phpunit/php/env[@name="SYMFONY_DEPRECATIONS_HELPER"]')->length) {
      $element = $doc->createElement('env');
      $element->setAttribute('name', 'SYMFONY_DEPRECATIONS_HELPER');
      $element->setAttribute('value', 'disabled');
      $xpath->query('//phpunit/php')
        ->item(0)
        ->appendChild($element);
      $doc->save($path);
    }
  }

  /**
   * Sets the mink driver arguments.
   *
   * @param string $path
   *   The path.
   * @param \DOMDocument $doc
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   The XPath object.
   */
  private function setMinkDriverArguments(string $path, \DOMDocument $doc, \DOMXPath $xpath): void {
    // Create an <env> element containing a JSON array which will control how
    // the Mink driver interacts with Chromedriver.
    $mink_arguments = json_encode([
      'chrome',
      [
        'chrome' => [
          // Start Chrome in headless mode.
          'switches' => ['headless', 'disable-gpu'],
        ],
      ],
      'http://localhost:4444',
    ], JSON_UNESCAPED_SLASHES);

    $expression = '//phpunit/php/env[@name="MINK_DRIVER_ARGS_WEBDRIVER"]';

    if (!$xpath->query($expression)->length) {
      $element = $doc->createElement('env');
      $element->setAttribute('name', 'MINK_DRIVER_ARGS_WEBDRIVER');
      $xpath->query('//phpunit/php')
        ->item(0)
        ->appendChild($element);
    }

    $xpath->query($expression)
      ->item(0)
      ->setAttribute('value', $mink_arguments);

    // When dumping the XML document tree, PHP will encode all double quotes in
    // the JSON string to &quot;, since the XML attribute value is itself
    // enclosed in double quotes. There's no way to change this behavior, so
    // we must do a string replacement in order to wrap the Mink driver
    // arguments in single quotes.
    // @see https://stackoverflow.com/questions/5473520/php-dom-and-single-quotes#5473718
    $search = sprintf('value="%s"', htmlentities($mink_arguments));
    $replace = sprintf("value='%s'", $mink_arguments);
    $xml = str_replace($search, $replace, $doc->saveXML());
    file_put_contents($path, $xml);
  }

  /**
   * Gets the directory to find tests under.
   *
   * @return string
   */
  private function testsDirectory(): string {
    // Default to the product module install path so as to include all modules.
    $directory = $this->facade->productModuleInstallPath();

    $composer_config = $this->loadComposerJson();
    if (!empty($composer_config['extra']['orca']['sut'])) {
      $sut = $composer_config['extra']['orca']['sut'];
      // Only limit the tests run for a SUT-only fixture.
      if (!empty($composer_config['extra']['orca']['sut-only'])) {
        $module = $this->productData->projectName($sut);
        $directory = $this->facade->productModuleInstallPath($module);
      }
    }

    return $directory;
  }

  /**
   * Loads the fixture's composer.json data.
   */
  private function loadComposerJson(): array {
    $json = file_get_contents($this->facade->rootPath('composer.json'));
    return json_decode($json, TRUE);
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
      ->in($this->testsDirectory())
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
