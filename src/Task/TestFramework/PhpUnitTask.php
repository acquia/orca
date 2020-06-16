<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs PHPUnit tests.
 */
class PhpUnitTask extends TestFrameworkBase {

  use SutSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHPUnit';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    $which = ($this->isPublicTestsOnly()) ? 'public' : 'all';
    return "Running {$which} PHPUnit tests";
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->overrideConfig();
    $this->ensurePhpUnitConfig();
    $this->runPhpUnit();
    $this->restoreConfig();
  }

  /**
   * Ensures that PHPUnit is properly configured.
   */
  private function ensurePhpUnitConfig() {
    $path = $this->fixture->getPath('docroot/core/phpunit.xml');
    $doc = new \DOMDocument();
    $doc->load($path);
    $xpath = new \DOMXPath($doc);

    $this->ensureSimpleTestDirectory();
    $this->setSimpletestSettings($path, $doc, $xpath);
    $this->setTestSuite($path, $doc, $xpath);
    $this->enableDrupalTestTraits($path, $doc, $xpath);
    $this->disableSymfonyDeprecationsHelper($path, $doc, $xpath);
    $this->setMinkDriverArguments($path, $doc, $xpath);
  }

  /**
   * Ensures that the Simpletest files directory exists.
   */
  private function ensureSimpleTestDirectory(): void {
    $this->filesystem->mkdir($this->fixture->getPath('docroot/sites/simpletest'));
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
      ->setAttribute('value', sprintf('http://%s', Fixture::WEB_ADDRESS));
    $xpath->query('//phpunit/php/env[@name="SIMPLETEST_DB"]')
      ->item(0)
      ->setAttribute('value', 'sqlite://localhost/sites/default/files/.ht.sqlite');
    $doc->save($path);
  }

  /**
   * Sets TestSuite config in phpunit.xml.
   *
   * @param string $path
   *   The path.
   * @param \DOMDocument $doc
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   The XPath object.
   */
  private function setTestSuite(string $path, \DOMDocument $doc, \DOMXPath $xpath): void {
    $directory = $doc->createElement('directory', $this->getPath());
    $exclude = $doc->createElement('exclude', "{$this->getPath()}/vendor");
    $testsuite = $doc->createElement('testsuite');
    $testsuite->setAttribute('name', 'orca');
    $testsuite->appendChild($directory);
    $testsuite->appendChild($exclude);
    $xpath->query('//phpunit/testsuites')
      ->item(0)
      ->appendChild($testsuite);
    $doc->save($path);
  }

  /**
   * Sets PHPUnit environment variables so that Drupal Test Traits can work.
   *
   * @param string $path
   *   The path.
   * @param \DOMDocument $doc
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   The XPath object.
   */
  private function enableDrupalTestTraits(string $path, \DOMDocument $doc, \DOMXPath $xpath): void {
    // The bootstrap script is located in ORCA's vendor directory, not the
    // fixture's, since ORCA controls the available test frameworks and
    // infrastructure.
    $xpath->query('//phpunit')
      ->item(0)
      ->setAttribute('bootstrap', "{$this->projectDir}/vendor/weitzman/drupal-test-traits/src/bootstrap.php");

    $this->setEnvironmentVariable('DTT_BASE_URL', sprintf('http://%s', Fixture::WEB_ADDRESS), $doc, $xpath);
    $this->setEnvironmentVariable('DTT_MINK_DRIVER_ARGS', $this->getMinkWebDriverArguments(), $doc, $xpath);
    $this->saveConfiguration($path, $doc);
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
    // Before Drupal 8.6, the tag in question was present and merely needed the
    // value changed. Since Drupal 8.6, the tag in question has been commented
    // out and must be re-created rather than modified directly.
    $this->setEnvironmentVariable('SYMFONY_DEPRECATIONS_HELPER', 'disabled', $doc, $xpath);
    $doc->save($path);
  }

  /**
   * Sets an environment variable in the PHPUnit configuration.
   *
   * @param string $name
   *   The name of the variable to set.
   * @param string $value
   *   The value of the variable to set.
   * @param \DOMDocument $document
   *   The DOM document.
   * @param \DOMXPath $xpath
   *   The XPath object.
   */
  private function setEnvironmentVariable(string $name, string $value, \DOMDocument $document, \DOMXPath $xpath): void {
    $result = $xpath->query(sprintf('//phpunit/php/env[@name="%s"]', $name));

    if ($result->length) {
      $element = $result->item(0);
      $element->setAttribute('value', $value);
    }
    else {
      $element = $document->createElement('env');
      $element->setAttribute('name', $name);
      $element->setAttribute('value', $value);
      $xpath->query('//phpunit/php')
        ->item(0)
        ->appendChild($element);
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
    $this->setEnvironmentVariable('MINK_DRIVER_ARGS_WEBDRIVER', $this->getMinkWebDriverArguments(), $doc, $xpath);
    $this->saveConfiguration($path, $doc);
  }

  /**
   * Saves the PHPUnit configuration.
   *
   * When dumping the XML document tree, PHP will encode all double quotes in
   * the JSON string to &quot;, since the XML attribute value is itself enclosed
   * in double quotes. There's no way to change this behavior, so we must do a
   * string replacement in order to wrap the Mink driver arguments in single
   * quotes.
   *
   * @param string $path
   *   The path where the configuration should be written.
   * @param \DOMDocument $doc
   *   The DOM document.
   *
   * @see https://stackoverflow.com/questions/5473520/php-dom-and-single-quotes#5473718
   */
  private function saveConfiguration(string $path, \DOMDocument $doc): void {
    $mink_arguments = $this->getMinkWebDriverArguments();
    $search = sprintf('value="%s"', htmlentities($mink_arguments));
    $replace = sprintf("value='%s'", $mink_arguments);
    $xml = str_replace($search, $replace, $doc->saveXML());
    file_put_contents($path, $xml);
  }

  /**
   * Returns JSON-encoded arguments for the Mink WebDriver driver.
   *
   * @return string
   *   The arguments for the WebDriver Mink driver, encoded as JSON.
   */
  private function getMinkWebDriverArguments(): string {
    return json_encode([
      'chrome',
      [
        'chrome' => [
          // Start Chrome in headless mode.
          'switches' => [
            'headless',
            'disable-gpu',
            'no-sandbox',
            'disable-dev-shm-usage',
            'disable-extensions',
          ],
        ],
      ],
      'http://localhost:4444',
    ], JSON_UNESCAPED_SLASHES);
  }

  /**
   * Runs PHPUnit.
   *
   * @throws \Acquia\Orca\Exception\TaskFailureException
   */
  protected function runPhpUnit(): void {
    try {
      $command = [
        'phpunit',
        '--colors=always',
        '--debug',
        "--configuration={$this->fixture->getPath('docroot/core/phpunit.xml')}",
        '--exclude-group=orca_ignore',
        '--testsuite=orca',
      ];
      if ($this->isPublicTestsOnly()) {
        $command[] = '--group=orca_public';
      }
      $this->processRunner->runFixtureVendorBin($command);
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

  /**
   * Overrides the active configuration.
   */
  public function overrideConfig(): void {
    $this->configFileOverrider->setPaths(
      $this->fixture->getPath('docroot/core/phpunit.xml.dist'),
      $this->fixture->getPath('docroot/core/phpunit.xml')
    );
    $this->configFileOverrider->override();
  }

  /**
   * Restores the previous configuration.
   */
  public function restoreConfig(): void {
    $this->configFileOverrider->restore();
  }

}
