<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\CiJobEnum;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Acquia\Orca\Enum\CiJobEnum
 */
class CiJobEnumTest extends TestCase {

  public function testCiJob(): void {
    $keys = CiJobEnum::keys();
    $values = array_values(CiJobEnum::toArray());
    $descriptions = CiJobEnum::descriptions();

    self::assertEquals($keys, $values, 'Keys and values match.');
    self::assertEquals($keys, array_keys($descriptions), 'Provided descriptions for all values.');
  }

  /**
   * @dataProvider providerDescriptions
   */
  public function testDescriptions($description): void {
    self::assertTrue(is_string($description), 'Description is a string.');
    self::assertNotEmpty($description, 'Description is non-empty.');
  }

  public function providerDescriptions(): array {
    $descriptions = [];
    foreach (CiJobEnum::descriptions() as $value) {
      $descriptions[] = [$value];
    }
    return $descriptions;
  }

}
