<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use PHPUnit\Framework\TestCase;

class DrupalCoreVersionEnumTest extends TestCase {

  use DrupalCoreVersionEnumsTestTrait;

  public function testKeyValuesAndDescriptions(): void {
    $keys = DrupalCoreVersionEnum::keys();
    $values = array_values(DrupalCoreVersionEnum::toArray());
    $descriptions = DrupalCoreVersionEnum::descriptions();

    self::assertEquals($keys, $values, 'Keys and values match.');
    self::assertEquals($keys, array_keys($descriptions), 'Provided descriptions for all values.');
  }

  /**
   * @dataProvider providerVersions
   */
  public function testGetDescription($version) {
    $description = $version->getDescription();

    /* @noinspection PhpUnitTestsInspection */
    self::assertTrue(is_string($description), 'Provided a description');
    self::assertNotEmpty($description, 'Description is not empty.');
  }

}
