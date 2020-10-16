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
    $examples = DrupalCoreVersionEnum::examples();

    self::assertEquals($keys, $values, 'Keys and values match.');
    self::assertEquals($keys, array_keys($descriptions), 'Provided descriptions for all values.');
    self::assertEquals($keys, array_keys($examples), 'Provided examples for all values.');
  }

  /**
   * @dataProvider providerVersions
   */
  public function testGetDescriptionAndExample($version) {
    $description = $version->getDescription();
    $example = $version->getExample();

    /* @noinspection PhpUnitTestsInspection */
    self::assertTrue(is_string($description), 'Provided a description');
    self::assertNotEmpty($description, 'Description is not empty.');
    self::assertNotEmpty($example, 'Example is not empty.');
  }

  public function testGetCommandArgumentHelp() {
    $help = DrupalCoreVersionEnum::commandArgumentHelp();

    self::assertCount(count(DrupalCoreVersionEnum::keys()) + 1, $help, 'Provided descriptions for all values.');
  }

  public function testUniqueDescriptions(): void {
    $all = DrupalCoreVersionEnum::descriptions();
    $unique = array_unique($all);

    self::assertSame($unique, $all, 'No duplicate descriptions.');
  }

  public function testUniqueExamples(): void {
    $all = DrupalCoreVersionEnum::examples();
    $unique = array_unique($all);

    self::assertSame($unique, $all, 'No duplicate examples.');
  }

}
