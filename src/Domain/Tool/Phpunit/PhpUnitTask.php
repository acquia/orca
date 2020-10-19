<?php

namespace Acquia\Orca\Domain\Tool\Phpunit;

use Acquia\Orca\Domain\Server\WebServer;
use Acquia\Orca\Domain\Tool\TestFrameworkBase;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Acquia\Orca\Helper\SutSettingsTrait;
use DOMDocument;
use DOMXPath;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs PHPUnit tests.
 */
class PhpUnitTask extends TestFrameworkBase {

  use SutSettingsTrait;

  /**
   * The DOM document for the PHPUnit configuration file.
   *
   * @var \DOMDocument
   */
  private $doc;

  /**
   * The XPath helper for the DOM document.
   *
   * @var \DOMXPath
   */
  private $xpath;

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
  private function ensurePhpUnitConfig(): void {
    $path = $this->fixture->getPath('docroot/core/phpunit.xml');
    $this->doc = new DOMDocument($path);
    $this->doc->load($path);
    $this->xpath = new DOMXPath($this->doc);

    $this->ensureSimpleTestDirectory();
    $this->setSimpletestSettings();
    $this->setTestSuite();
    $this->enableDrupalTestTraits();
    $this->disableSymfonyDeprecationsHelper();
    $this->setMinkDriverArguments();
    $this->writeConfiguration($path);
  }

  /**
   * Ensures that the Simpletest files directory exists.
   */
  private function ensureSimpleTestDirectory(): void {
    $this->filesystem->mkdir($this->fixture->getPath('docroot/sites/simpletest'));
  }

  /**
   * Sets Simpletest settings.
   */
  private function setSimpletestSettings(): void {
    $this->xpath->query('//phpunit/php/env[@name="SIMPLETEST_BASE_URL"]')
      ->item(0)
      ->setAttribute('value', sprintf('http://%s', WebServer::WEB_ADDRESS));
    $this->xpath->query('//phpunit/php/env[@name="SIMPLETEST_DB"]')
      ->item(0)
      ->setAttribute('value', 'sqlite://localhost/sites/default/files/.ht.sqlite');
  }

  /**
   * Sets TestSuite config in phpunit.xml.
   */
  private function setTestSuite(): void {
    $directory = $this->doc->createElement('directory', $this->getPath());
    $exclude = $this->doc->createElement('exclude', "{$this->getPath()}/vendor");
    $testsuite = $this->doc->createElement('testsuite');
    $testsuite->setAttribute('name', 'orca');
    $testsuite->appendChild($directory);
    $testsuite->appendChild($exclude);
    $this->xpath->query('//phpunit/testsuites')
      ->item(0)
      ->appendChild($testsuite);
  }

  /**
   * Sets PHPUnit environment variables so that Drupal Test Traits can work.
   */
  private function enableDrupalTestTraits(): void {
    // The bootstrap script is located in ORCA's vendor directory, not the
    // fixture's, since ORCA controls the available test frameworks and
    // infrastructure.
    $this->xpath->query('//phpunit')
      ->item(0)
      ->setAttribute('bootstrap', $this->orca->getPath('vendor/weitzman/drupal-test-traits/src/bootstrap.php'));

    $this->setEnvironmentVariable('DTT_BASE_URL', sprintf('http://%s', WebServer::WEB_ADDRESS));
    $this->setEnvironmentVariable('DTT_MINK_DRIVER_ARGS', $this->getMinkWebDriverArguments());
  }

  /**
   * Disables the Symfony Deprecations Helper.
   */
  private function disableSymfonyDeprecationsHelper(): void {
    $this->setEnvironmentVariable('SYMFONY_DEPRECATIONS_HELPER', 'disabled');
  }

  /**
   * Sets an environment variable in the PHPUnit configuration.
   *
   * @param string $name
   *   The name of the variable to set.
   * @param string $value
   *   The value of the variable to set.
   */
  private function setEnvironmentVariable(string $name, string $value): void {
    $result = $this->xpath->query(sprintf('//phpunit/php/env[@name="%s"]', $name));

    if ($result->length) {
      $element = $result->item(0);
      $element->setAttribute('value', $value);
    }
    else {
      $element = $this->doc->createElement('env');
      $element->setAttribute('name', $name);
      $element->setAttribute('value', $value);
      $this->xpath->query('//phpunit/php')
        ->item(0)
        ->appendChild($element);
    }
  }

  /**
   * Sets the mink driver arguments.
   */
  private function setMinkDriverArguments(): void {
    // Create an <env> element containing a JSON array which will control how
    // the Mink driver interacts with Chromedriver.
    $this->setEnvironmentVariable('MINK_DRIVER_ARGS_WEBDRIVER', $this->getMinkWebDriverArguments());
  }

  /**
   * Writes the PHPUnit configuration to disk.
   *
   * When dumping the XML document tree, PHP will encode all double quotes in
   * the JSON string to &quot;, since the XML attribute value is itself
   * enclosed in double quotes. There's no way to change this behavior, so we
   * must do a string replacement in order to wrap the Mink driver arguments
   * in single quotes.
   *
   * @param string $path
   *   The path at which to write the configuration.
   *
   * @see https://stackoverflow.com/questions/5473520/php-dom-and-single-quotes#5473718
   */
  private function writeConfiguration(string $path): void {
    $mink_arguments = $this->getMinkWebDriverArguments();
    $search = sprintf('value="%s"', htmlentities($mink_arguments));
    $replace = sprintf("value='%s'", $mink_arguments);
    $xml = str_replace($search, $replace, $this->doc->saveXML());
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
   * @throws \Acquia\Orca\Exception\OrcaTaskFailureException
   */
  protected function runPhpUnit(): void {
    try {
      $command = [
        'phpunit',
        '--verbose',
      ];
      if ($this->isToGenerateCodeCoverage()) {
        $command[] = "--coverage-clover={$this->cloverCoverage}";
      }
      $command = array_merge($command, [
        '--colors=always',
        '--debug',
        "--configuration={$this->fixture->getPath('docroot/core/phpunit.xml')}",
        '--exclude-group=orca_ignore',
        '--testsuite=orca',
      ]);
      if ($this->isPublicTestsOnly()) {
        $command[] = '--group=orca_public';
      }
      $this->processRunner->runFixtureVendorBin($command);
    }
    catch (ProcessFailedException $e) {
      throw new OrcaTaskFailureException($e->getMessage());
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
