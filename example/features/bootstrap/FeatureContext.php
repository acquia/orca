<?php

use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 *
 * @property int $sum
 */
class FeatureContext implements Context {

  /**
   * @Given /^I add (\d+) to (\d+)$/
   *
   * @param int $addend
   *   The addend.
   * @param int $augend
   *   The augend.
   */
  public function iAddTwoNumber(int $addend, int $augend) {
    $this->sum = $augend + $addend;
  }

  /**
   * @Then /^I get the sum (\d+)$/
   *
   * @param int $sum
   *   The sum.
   *
   * @throws \Exception
   */
  public function iGetTheSum(int $sum) {
    if ($this->sum !== $sum) {
      throw new \Exception();
    }
  }

}
