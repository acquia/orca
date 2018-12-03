<?php

use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 *
 * @property int $sum
 */
class FeatureContext implements Context {

  /**
   * @Given /^I add (\d+) and (\d+)$/
   *
   * @param int $augend
   *   The augend.
   * @param int $addend
   *   The addend.
   */
  public function iAddTwoNumbers(int $augend, int $addend) {
    $this->sum = $augend + $addend;
  }

  /**
   * @Then /^I get (\d+)$/
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
