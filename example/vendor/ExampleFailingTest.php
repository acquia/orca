<?php

namespace Drupal\Tests\example\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Vendor packages (installed via a SUT) often provide tests that should not be
 * run by ORCA. This failing test is used to verify that ORCA excludes the
 * vendor directory as expected.
 */
class ExampleFailingTest extends TestCase
{

    /**
     * ORCA should never run this test.
     */
    public function testExcludeVendorDirectory(): void
    {
        self::assertTrue(false);
    }

}
