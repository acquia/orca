<?php

/**
 * An example class to provide some cyclomatic complexity for PHPLOC to report.
 */
class ExampleComplexity {

  /**
   * Creates some cyclomatic complexity.
   */
  public function createComplexity(): int {
    $x = 0;
    for ($y = 0; $y < 10; $y++) {
      try {
        $x += random_int(0, 10);
      }
      catch (Exception $e) {
        $x++;
      }
    }
    return $x;
  }

}
