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
      ->exists(self::validSutName())
      ->willReturn(TRUE);
  }

  private function createCiRunOptions($options): CiRunOptions {
    $package_manager = $this->packageManager->reveal();
    return new CiRunOptions($package_manager, $options);
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
      'phase' => self::validPhaseName(),
      'sut' => self::validSutName(),
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
      'job' => self::validJobName(),
      'phase' => strtolower($phase->getKey()),
      'sut' => self::validSutName(),
    ]);

    self::assertEquals($phase, $options->getPhase(), 'Set/got "phase" option.');
  }

  public function testValidSut(): void {
    $options = $this->createCiRunOptions([
      'job' => self::validJobName(),
      'phase' => self::validPhaseName(),
      'sut' => self::validSutName(),
    ]);
    $this->packageManager
      ->get(self::validSutName())
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
    return [
      'No options' => [[]],
      'Missing job' => [
        [
          'phase' => self::validPhaseName(),
          'sut' => self::validSutName(),
        ],
      ],
      'Missing phase' => [
        [
          'job' => self::validJobName(),
          'sut' => self::validSutName(),
        ],
      ],
      'Missing sut' => [
        [
          'phase' => self::validPhaseName(),
          'job' => self::validJobName(),
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
      'job' => self::validJobName(),
      'phase' => self::validPhaseName(),
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
    return [
      'Non-existent phase' => [
        [
          'job' => self::validJobName(),
          'phase' => 'invalid',
          'sut' => self::validSutName(),
        ],
      ],
      'Non-existent job' => [
        [
          'job' => 'invalid',
          'phase' => self::validPhaseName(),
          'sut' => self::validSutName(),
        ],
      ],
      'Non-string phase' => [
        [
          'job' => self::validJobName(),
          'phase' => 12345,
          'sut' => self::validSutName(),
        ],
      ],
      'Non-string job' => [
        [
          'job' => 12345,
          'phase' => self::validPhaseName(),
          'sut' => self::validSutName(),
        ],
      ],
      'Non-string sut' => [
        [
          'job' => self::validJobName(),
          'phase' => self::validPhaseName(),
          'sut' => 12345,
        ],
      ],
      'Invalid sut' => [
        [
          'job' => self::validJobName(),
          'phase' => self::validPhaseName(),
          'sut' => 'invalid',
        ],
      ],
    ];
  }

}
