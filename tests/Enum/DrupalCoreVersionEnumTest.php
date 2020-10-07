<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use PHPUnit\Framework\TestCase;

class DrupalCoreVersionEnumTest extends TestCase {

  public function testCiJob(): void {
    $keys = DrupalCoreVersionEnum::keys();
    $values = array_values(DrupalCoreVersionEnum::toArray());
    $descriptions = DrupalCoreVersionEnum::descriptions();

    self::assertEquals($keys, $values, 'Keys and values match.');
    self::assertEquals($keys, array_keys($descriptions), 'Provided descriptions for all values.');
  }

}
