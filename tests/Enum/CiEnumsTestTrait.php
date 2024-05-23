<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;

trait CiEnumsTestTrait {

  public static function providerJobs(): array {
    $jobs = CiJobEnum::values();
    array_walk($jobs, static function (&$value) {
      $value = [$value];
    });
    return $jobs;
  }

  public static function providerPhases(): array {
    $phases = CiJobPhaseEnum::values();
    array_walk($phases, static function (&$value) {
      $value = [$value];
    });
    return $phases;
  }

  protected static function validJob(): CiJobEnum {
    $jobs = CiJobEnum::values();
    return reset($jobs);
  }

  protected static function validJobName(): string {
    return self::validJob()->getKey();
  }

  protected static function validPhase(): CiJobPhaseEnum {
    return CiJobPhaseEnum::SCRIPT();
  }

  protected static function validPhaseName(): string {
    return strtolower(self::validPhase()->getValue());
  }

  protected static function validSutName(): string {
    return 'drupal/example';
  }

}
