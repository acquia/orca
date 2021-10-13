<?php

namespace Acquia\Orca\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends PHPUnitTestCase {
  use ProphecyTrait;

}
