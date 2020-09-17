<?php

namespace Acquia\Orca\Tests\Options;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Options\CiRunOptions;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @coversDefaultClass \Acquia\Orca\Options\CiRunOptions
 */
class CiRunOptionsTest extends TestCase {

  use CiEnumsTestTrait;

  /**
   * @dataProvider providerJobs
   *
   * @covers ::__construct
   * @covers ::getJob
   * @covers ::isValidJobValue
   * @covers ::resolve
   */
  public function testValidJobs(CiJobEnum $job): void {
    $options = new CiRunOptions([
      'job' => $job->getKey(),
      'phase' => $this->validPhaseName(),
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
    $options = new CiRunOptions([
      'job' => $this->validJobName(),
      'phase' => strtolower($phase->getKey()),
    ]);

    self::assertEquals($phase, $options->getPhase(), 'Set/got "phase" option.');
  }

  /**
   * @dataProvider providerMissingRequiredOptions
   */
  public function testMissingRequiredOptions($options): void {
    $this->expectException(MissingOptionsException::class);

    new CiRunOptions($options);
  }

  public function providerMissingRequiredOptions(): array {
    return [
      [[]],
      [['phase' => $this->validPhaseName()]],
      [['job' => $this->validJobName()]],
    ];
  }

  /**
   * @covers ::resolve
   */
  public function testUndefinedOptions(): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    new CiRunOptions([
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

    new CiRunOptions($options);
  }

  public function providerInvalidOptions(): array {
    return [
      [['job' => $this->validJobName(), 'phase' => 12345]],
      [['job' => 12345, 'phase' => $this->validPhaseName()]],
      [['job' => $this->validJobName(), 'phase' => 'invalid']],
      [['job' => 'invalid', 'phase' => $this->validPhaseName()]],
    ];
  }

}
