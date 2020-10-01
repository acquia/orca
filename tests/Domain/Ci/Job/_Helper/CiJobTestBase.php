<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job\_Helper;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
abstract class CiJobTestBase extends TestCase {

  protected const SUT_REPOSITORY_URL_ABSOLUTE = '/var/www/sut';

  public function setUp(): void {
    // SUT (package).
    $sut = $this->prophesize(Package::class);
    $sut->getPackageName()
      ->willReturn($this->validSutName());
    $sut->getRepositoryUrlAbsolute()
      ->willReturn(self::SUT_REPOSITORY_URL_ABSOLUTE);

    // Package manager.
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists($this->validSutName())
      ->willReturn(TRUE);
    $this->packageManager
      ->get($this->validSutName())
      ->willReturn($sut);

    // Process runner.
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrca(Argument::any())
      ->willReturn(0);
  }

  protected function createCiRunOptions($options): CiRunOptions {
    $package_manager = $this->packageManager->reveal();
    return new CiRunOptions($package_manager, $options);
  }

  protected function createValidRunOptions(): CiRunOptions {
    return $this->createCiRunOptions($this->validRawOptions());
  }

  protected function validRawOptions(): array {
    return [
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ];
  }

  protected function validSutName(): string {
    return 'drupal/example';
  }

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
