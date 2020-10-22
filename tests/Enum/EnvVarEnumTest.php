<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\EnvVarEnum;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Acquia\Orca\Enum\EnvVarEnum
 */
class EnvVarEnumTest extends TestCase {

  public function testEnvVar(): void {
    $values = array_values(EnvVarEnum::toArray());
    $descriptions = EnvVarEnum::descriptions();

    self::assertEquals($values, array_keys($descriptions), 'Provided descriptions for all values.');
  }

  /**
   * @dataProvider providerKeyValuePairs
   */
  public function testKeyValuePairs($key, $value): void {
    self::assertEquals($key, $value, 'Key and value correspond.');
  }

  public function providerKeyValuePairs(): array {
    $pairs = [];
    foreach (EnvVarEnum::toArray() as $key => $value) {
      $pairs[$key] = [$key, $value];
    }
    return $pairs;
  }

  public function testGetDescription() {
    $env = EnvVarEnum::ORCA_JOB();
    $descriptions = EnvVarEnum::descriptions();
    $expected = $descriptions[$env->getKey()];

    self::assertSame($expected, $env->getDescription(), 'Got the correct description');
  }

  /**
   * @dataProvider providerDescriptions
   */
  public function testDescriptions($description): void {
    /* @noinspection PhpUnitTestsInspection */
    self::assertTrue(is_string($description), 'Description is a string.');
    self::assertNotEmpty($description, 'Description is non-empty.');
  }

  public function providerDescriptions(): array {
    $descriptions = [];
    foreach (EnvVarEnum::descriptions() as $key => $value) {
      $descriptions[$key] = [$value];
    }
    return $descriptions;
  }

}
