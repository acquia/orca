<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\TestCase;

/**
 * @coversDefaultClass \Acquia\Orca\Enum\CiJobPhaseEnum
 */
class CiJobPhaseEnumTest extends TestCase {

  public function testCiBuildPhase(): void {
    $values = array_values(CiJobPhaseEnum::toArray());
    $descriptions = CiJobPhaseEnum::descriptions();

    self::assertEquals($values, array_keys($descriptions), 'Provided descriptions for all values.');
  }

  /**
   * @dataProvider providerKeyValuePairs
   */
  public function testKeyValuePairs($key, $value): void {
    self::assertEquals(strtolower($key), $value, 'Key and value correspond.');
  }

  public function providerKeyValuePairs(): array {
    $pairs = [];
    foreach (CiJobPhaseEnum::toArray() as $key => $value) {
      $pairs[] = [$key, $value];
    }
    return $pairs;
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
    foreach (CiJobPhaseEnum::descriptions() as $value) {
      $descriptions[] = [$value];
    }
    return $descriptions;
  }

}
