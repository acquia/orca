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
   * Provides a dummy step.
   *
   * @Given /^I tag a scenario @orca_ignore$/
   * @When /^I run ORCA tests/
   */
  public function dummyStep() {
  }

  /**
   * Visits the homepage.
   *
   * @Given /^I visit the homepage$/
   */
  public function iVisitTheHomePage() {
    $this->response = (new Client())
      ->request('GET', 'http://127.0.0.1:8080');
  }

  /**
   * Asserts that a given HTTP status code was received.
   *
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
   * Asserts that the tagged scenario isn't run.
   *
   * @Then /^the tagged scenario should not be run$/
   */
  public function theTaggedScenarioShouldNotBeRun() {
    throw new \Exception('An ignored scenario was run');
  }

}
