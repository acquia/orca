<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;

trait CiEnumsTestTrait {

  public function providerJobs(): array {
    $jobs = CiJobEnum::keys();
    array_walk($jobs, static function (&$value) {
      $value = [$value];
    });
    return $jobs;
  }

  public function providerPhases(): array {
    $phases = CiJobPhaseEnum::keys();
    array_walk($phases, static function (&$value) {
      $value = [strtolower($value)];
    });
    return $phases;
  }

  private function validJob(): string {
    $jobs = CiJobEnum::keys();
    return reset($jobs);
  }

  private function validPhase(): string {
    $phases = CiJobPhaseEnum::keys();
    return strtolower(reset($phases));
  }

}
