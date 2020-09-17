<?php

namespace Acquia\Orca\Tests\Console\Command\Ci;

use Acquia\Orca\Console\Command\Ci\CiRunCommand;
use Acquia\Orca\Domain\Ci\CiJobFactory;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Acquia\Orca\Tests\Domain\Ci\Job\CiTestJob;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Domain\Ci\Job\AbstractCiJob|\Prophecy\Prophecy\ObjectProphecy $ciJob
 * @property \Acquia\Orca\Domain\Ci\CiJobFactory|\Prophecy\Prophecy\ObjectProphecy $jobFactory
 * @coversDefaultClass \Acquia\Orca\Console\Command\Ci\CiRunCommand
 */
class CiRunCommandTest extends CommandTestBase {

  use CiEnumsTestTrait;

  protected function setUp(): void {
    $this->ciJob = $this->prophesize(CiTestJob::class);
    $this->jobFactory = $this->prophesize(CiJobFactory::class);
  }

  protected function createCommand(): Command {
    $this->jobFactory
      ->create($this->validJob())
      ->willReturn($this->ciJob->reveal());
    $job_factory = $this->jobFactory->reveal();
    return new CiRunCommand($job_factory);
  }

  /**
   * @covers ::__construct
   * @covers ::configure
   * @covers ::formatArgumentDescription
   * @covers ::getJobArgumentDescription
   * @covers ::getPhaseArgumentDescription
   */
  public function testBasicConfiguration(): void {
    $command = $this->createCommand();

    $definition = $command->getDefinition();
    $arguments = $definition->getArguments();
    $job = $definition->getArgument('job');
    $build_phase = $definition->getArgument('phase');
    $options = $definition->getOptions();

    self::assertEquals('ci:run', $command->getName(), 'Set correct name.');
    self::assertEquals(['run'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals(['job', 'phase'], array_keys($arguments), 'Set correct arguments.');
    self::assertTrue($job->isRequired(), 'Required job argument.');
    self::assertTrue($build_phase->isRequired(), 'Required phase argument.');
    self::assertEquals([], array_keys($options), 'Set correct options.');
  }

  public function testExecution(): void {
    $this->ciJob
      ->script()
      ->shouldBeCalledOnce();

    $this->executeCommand([
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
    ]);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testInvalidOptions(): void {
    $this->executeCommand([
      'job' => 'invalid',
      'phase' => 'invalid',
    ]);

    self::assertStringStartsWith('Error: ', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

}
