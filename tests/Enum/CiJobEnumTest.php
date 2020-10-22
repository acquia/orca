<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
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
  public function testValidDescriptions($job, $description): void {
    self::assertSame($description, $job->getDescription(), 'Returned correct description.');
    self::assertNotEmpty($description, 'Description is non-empty.');
  }

  public function providerDescriptions(): array {
    $descriptions = [];
    foreach (CiJobEnum::descriptions() as $key => $value) {
      $descriptions[$key] = [new CiJobEnum($key), $value];
    }
    return $descriptions;
  }

  public function testUniqueDescriptions(): void {
    $all_descriptions = CiJobEnum::descriptions();
    $unique_descriptions = array_unique($all_descriptions);

    self::assertSame($unique_descriptions, $all_descriptions, 'No duplicate descriptions.');
  }

  /**
   * @dataProvider providerDrupalCoreVersions
   */
  public function testGetDrupalCoreVersion($job): void {
    $version = $job->getDrupalCoreVersion();

    self::assertInstanceOf(DrupalCoreVersionEnum::class, $version, 'Returned a version enum.');
  }

  public function providerDrupalCoreVersions(): array {
    $jobs = CiJobEnum::values();
    unset($jobs[CiJobEnum::STATIC_CODE_ANALYSIS]);
    foreach ($jobs as $key => $value) {
      $jobs[$key] = [$value];
    }
    return $jobs;
  }

  public function testGetDrupalCoreVersionSpecialCase() {
    $job = CiJobEnum::STATIC_CODE_ANALYSIS();

    $version = $job->getDrupalCoreVersion();

    self::assertNull($version, 'Returned NULL.');
  }

}
