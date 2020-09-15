<?php

namespace Acquia\Orca\Tests\Options;

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
  public function testValidJobs($job): void {
    $options = new CiRunOptions([
      'job' => $job,
      'phase' => $this->validPhase(),
    ]);

    self::assertSame($job, $options->getJob(), 'Set/got "job" option.');
  }

  /**
   * @dataProvider providerPhases
   *
   * @covers ::__construct
   * @covers ::getPhase
   * @covers ::isValidPhaseValue
   * @covers ::resolve
   */
  public function testValidPhases($phase): void {
    $options = new CiRunOptions([
      'job' => $this->validJob(),
      'phase' => $phase,
    ]);

    self::assertSame($phase, $options->getPhase(), 'Set/got "phase" phase.');
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
      [['phase' => $this->validPhase()]],
      [['job' => $this->validJob()]],
    ];
  }

  /**
   * @covers ::resolve
   */
  public function testUndefinedOptions(): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    new CiRunOptions([
      'job' => $this->validJob(),
      'phase' => $this->validPhase(),
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
      [['job' => $this->validJob(), 'phase' => 12345]],
      [['job' => 12345, 'phase' => $this->validPhase()]],
      [['job' => $this->validJob(), 'phase' => 'invalid']],
      [['job' => 'invalid', 'phase' => $this->validPhase()]],
    ];
  }

}
