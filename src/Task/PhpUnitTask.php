<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs PHPUnit tests.
 */
class PhpUnitTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->ensurePhpUnitConfig();
    $this->runPhpUnit();
  }

  /**
   * Ensures that PHPUnit is properly configured.
   */
  private function ensurePhpUnitConfig() {
    $path = $this->fixture->docrootPath('core/phpunit.xml.dist');
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
      ->setAttribute('value', sprintf('http://%s', Fixture::WEB_ADDRESS));
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

    $name_attribute = 'MINK_DRIVER_ARGS_WEBDRIVER';
    $expression = "//phpunit/php/env[@name='{$name_attribute}']";

    if (!$xpath->query($expression)->length) {
      $element = $doc->createElement('env');
      $element->setAttribute('name', $name_attribute);
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
   * Runs PHPUnit.
   *
   * @throws \Acquia\Orca\Task\TaskFailureException
   */
  protected function runPhpUnit(): void {
    try {
      $this->processRunner->runVendorBinProcess([
        'phpunit',
        '--colors=always',
        '--stop-on-failure',
        "--configuration={$this->fixture->docrootPath('core/phpunit.xml.dist')}",
        "--bootstrap={$this->fixture->docrootPath('core/tests/bootstrap.php')}",
        "--group=orca_public",
        $this->fixture->testsDirectory(),
      ]);
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

}
