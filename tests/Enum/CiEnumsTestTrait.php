<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;

trait CiEnumsTestTrait {

  public function providerJobs(): array {
    $jobs = CiJobEnum::values();
    array_walk($jobs, static function (&$value) {
      $value = [$value];
    });
    return $jobs;
  }

  public function providerPhases(): array {
    $phases = CiJobPhaseEnum::values();
    array_walk($phases, static function (&$value) {
      $value = [$value];
    });
    return $phases;
  }

  protected function validJob(): CiJobEnum {
    $jobs = CiJobEnum::values();
    return reset($jobs);
  }

  protected function validJobName(): string {
    return $this->validJob()->getKey();
  }

  protected function validPhase(): CiJobPhaseEnum {
    return CiJobPhaseEnum::SCRIPT();
  }

  protected function validPhaseName(): string {
    return strtolower($this->validPhase()->getValue());
  }

}
