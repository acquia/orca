<?php

namespace Drupal\Tests\example\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Provides an example PHPUnit test for ORCA.
 */
class ExampleUnitTest extends TestCase {

  /**
   * Demonstrates designating a test to run only during own builds.
   */
  public function testPrivatePseudoGroup() {
    $this->assertTrue(TRUE, 'Performed private test.');
  }

  /**
   * Demonstrates designating a test to run during OTHER packages' builds.
   *
   * @group orca_public
   */
  public function testPublicGroup() {
    $this->assertTrue(TRUE, 'Performed public test.');
  }

  /**
   * Demonstrates designating a test to never run by ORCA.
   *
   * @group orca_ignore
   */
  public function testIgnoreGroup() {
    $this->fail('Ran ignored test.');
  }

}
