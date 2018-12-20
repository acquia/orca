<?php

use Behat\Behat\Context\Context;
use GuzzleHttp\Client;

/**
 * Defines application features from the specific context.
 *
 * @property mixed|\Psr\Http\Message\ResponseInterface $response
 */
class FeatureContext implements Context {

  /**
   * @Given /^I tag a scenario @orca_ignore$/
   * @When /^I run ORCA tests/
   */
  public function dummyStep() {
  }

  /**
   * @Given /^I visit the homepage$/
   */
  public function iVisitTheHomePage() {
    $this->response = (new Client())
      ->request('GET', 'http://127.0.0.1:8080');
  }

  /**
   * @Then /^I should get an HTTP (\d+) status code$/
   *
   * @throws \Exception
   */
  public function iShouldGetAnHttpStatusCode($status_code) {
    if ($this->response->getStatusCode() != $status_code) {
      throw new \Exception(sprintf('Got an HTTP %d status code.', $this->response->getStatusCode()));
    }
  }

  /**
   * @Then /^the tagged scenario should not be run$/
   */
  public function theTaggedScenarioShouldNotBeRun() {
    throw new \Exception('An ignored scenario was run');
  }

}
