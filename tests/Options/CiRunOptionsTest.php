<?php

namespace Acquia\Orca\Tests\Options;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Options\CiRunOptions;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @coversDefaultClass \Acquia\Orca\Options\CiRunOptions
 */
class CiRunOptionsTest extends TestCase {

  use CiEnumsTestTrait;

  protected PackageManager|ObjectProphecy $packageManager;

  protected function setUp(): void {
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists(Argument::any())
      ->willReturn(FALSE);
    $this->packageManager
      ->exists($this->validSutName())
      ->willReturn(TRUE);
  }

  private function createCiRunOptions($options): CiRunOptions {
    $package_manager = $this->packageManager->reveal();
    return new CiRunOptions($package_manager, $options);
  }

  private function validSutName(): string {
    return 'drupal/example';
  }

  private function validSut(): Package {
    $sut = $this->prophesize(Package::class);
    return $sut->reveal();
  }

  /**
   * @dataProvider providerJobs
   *
   * @covers ::__construct
   * @covers ::getJob
   * @covers ::isValidJobValue
   * @covers ::resolve
   */
  public function testValidJobs(CiJobEnum $job): void {
    $options = $this->createCiRunOptions([
      'job' => $job->getKey(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ]);

    self::assertEquals($job, $options->getJob(), 'Set/got "job" option.');
  }

  /**
   * @dataProvider providerPhases
   *
   * @covers ::__construct
   * @covers ::getPhase
   * @covers ::isValidPhaseValue
   * @covers ::resolve
   */
  public function testValidPhases(CiJobPhaseEnum $phase): void {
    $options = $this->createCiRunOptions([
      'job' => $this->validJobName(),
      'phase' => strtolower($phase->getKey()),
      'sut' => $this->validSutName(),
    ]);

    self::assertEquals($phase, $options->getPhase(), 'Set/got "phase" option.');
  }

  public function testValidSut(): void {
    $options = $this->createCiRunOptions([
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ]);
    $this->packageManager
      ->get($this->validSutName())
      ->willReturn($this->validSut());

    self::assertEquals($this->validSut(), $options->getSut(), 'Set/got "sut" option.');
  }

  /**
   * @dataProvider providerMissingRequiredOptions
   */
  public function testMissingRequiredOptions($options): void {
    $this->expectException(MissingOptionsException::class);

    $this->createCiRunOptions($options);
  }

  public static function providerMissingRequiredOptions(): array {
    $obj = new CiRunOptionsTest("testMissingRequiredOptions");
    return [
      'No options' => [[]],
      'Missing job' => [
        [
          'phase' => $obj->validPhaseName(),
          'sut' => $obj->validSutName(),
        ],
      ],
      'Missing phase' => [
        [
          'job' => $obj->validJobName(),
          'sut' => $obj->validSutName(),
        ],
      ],
      'Missing sut' => [
        [
          'phase' => $obj->validPhaseName(),
          'job' => $obj->validJobName(),
        ],
      ],
    ];
  }

  /**
   * @covers ::resolve
   */
  public function testUndefinedOptions(): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createCiRunOptions([
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
      'undefined' => 'option',
    ]);
  }

  /**
   * @dataProvider providerInvalidOptions
   *
   * @covers ::resolve
   * @covers ::isValidJobValue
   * @covers ::isValidPhaseValue
   */
  public function testInvalidOptions($options): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createCiRunOptions($options);
  }

  public static function providerInvalidOptions(): array {
    $obj = new CiRunOptionsTest("testInvalidOptions");
    return [
      'Non-existent phase' => [
        [
          'job' => $obj->validJobName(),
          'phase' => 'invalid',
          'sut' => $obj->validSutName(),
        ],
      ],
      'Non-existent job' => [
        [
          'job' => 'invalid',
          'phase' => $obj->validPhaseName(),
          'sut' => $obj->validSutName(),
        ],
      ],
      'Non-string phase' => [
        [
          'job' => $obj->validJobName(),
          'phase' => 12345,
          'sut' => $obj->validSutName(),
        ],
      ],
      'Non-string job' => [
        [
          'job' => 12345,
          'phase' => $obj->validPhaseName(),
          'sut' => $obj->validSutName(),
        ],
      ],
      'Non-string sut' => [
        [
          'job' => $obj->validJobName(),
          'phase' => $obj->validPhaseName(),
          'sut' => 12345,
        ],
      ],
      'Invalid sut' => [
        [
          'job' => $obj->validJobName(),
          'phase' => $obj->validPhaseName(),
          'sut' => 'invalid',
        ],
      ],
    ];
  }

}
