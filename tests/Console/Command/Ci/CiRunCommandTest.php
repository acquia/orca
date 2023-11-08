<?php

namespace Acquia\Orca\Tests\Console\Command\Ci;

use Acquia\Orca\Console\Command\Ci\CiRunCommand;
use Acquia\Orca\Domain\Ci\CiJobFactory;
use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Options\CiRunOptions;
use Acquia\Orca\Options\CiRunOptionsFactory;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiTestJob;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @property \Acquia\Orca\Domain\Ci\CiJobFactory|\Prophecy\Prophecy\ObjectProphecy $ciJobFactory
 * @property \Acquia\Orca\Domain\Ci\Job\AbstractCiJob|\Prophecy\Prophecy\ObjectProphecy $ciJob
 * @property \Acquia\Orca\Options\CiRunOptionsFactory|\Prophecy\Prophecy\ObjectProphecy $ciRunOptionsFactory
 * @property \Acquia\Orca\Options\CiRunOptions|\Prophecy\Prophecy\ObjectProphecy $ciRunOptions
 * @property \Symfony\Component\EventDispatcher\EventDispatcher|\Prophecy\Prophecy\ObjectProphecy $eventDispatcher
 * @property \Acquia\Orca\Helper\EnvFacade|\Prophecy\Prophecy\ObjectProphecy $env
 * @coversDefaultClass \Acquia\Orca\Console\Command\Ci\CiRunCommand
 */
class CiRunCommandTest extends CommandTestBase {

  use CiEnumsTestTrait;

  protected CiJobFactory|ObjectProphecy $ciJobFactory;
  protected AbstractCiJob|ObjectProphecy $ciJob;
  protected CiRunOptionsFactory|ObjectProphecy $ciRunOptionsFactory;
  protected CiRunOptions|ObjectProphecy $ciRunOptions;
  protected EventDispatcher|ObjectProphecy $eventDispatcher;
  protected EnvFacade|ObjectProphecy $env;

  protected function setUp(): void {
    $this->ciRunOptions = $this->prophesize(CiRunOptions::class);
    $this->ciRunOptionsFactory = $this->prophesize(CiRunOptionsFactory::class);
    $this->ciRunOptionsFactory
      ->create(Argument::any())
      ->willReturn($this->ciRunOptions->reveal());
    $this->ciJob = $this->prophesize(CiTestJob::class);
    $this->ciJob
      ->jobName()
      ->willReturn(new CiJobEnum(CiJobEnum::STATIC_CODE_ANALYSIS));
    $this->ciJobFactory = $this->prophesize(CiJobFactory::class);
    $this->ciJobFactory
      ->create($this->validJob())
      ->willReturn($this->ciJob->reveal());
    $this->eventDispatcher = $this->prophesize(EventDispatcher::class);
    $this->eventDispatcher
      ->dispatch(Argument::cetera())
      ->willReturn(new \stdClass());
    $this->env = $this->prophesize(EnvFacade::class);
    $this->env
      ->get(Argument::any())
      ->willReturn();
  }

  protected function createCommand(): Command {
    $ci_run_options_factory = $this->ciRunOptionsFactory->reveal();
    $ci_job_factory = $this->ciJobFactory->reveal();
    $event_dispatcher = $this->eventDispatcher->reveal();
    $env = $this->env->reveal();
    return new CiRunCommand($ci_job_factory, $ci_run_options_factory, $event_dispatcher, $env);
  }

  private function validSutName(): string {
    return 'drupal/example';
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
    $sut = $definition->getArgument('sut');
    $options = $definition->getOptions();

    self::assertEquals('ci:run', $command->getName(), 'Set correct name.');
    self::assertEquals(['run'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals(['job', 'phase', 'sut'], array_keys($arguments), 'Set correct arguments.');
    self::assertTrue($job->isRequired(), 'Required job argument.');
    self::assertTrue($build_phase->isRequired(), 'Required phase argument.');
    self::assertTrue($sut->isRequired(), 'Required SUT argument.');
    self::assertEquals([], array_keys($options), 'Set correct options.');
  }

  public function testExecution(): void {
    $this->ciRunOptions
      ->getJob()
      ->willReturn(new CiJobEnum($this->validJobName()));
    $this->ciRunOptions
      ->getPhase()
      ->willReturn(new CiJobPhaseEnum($this->validPhaseName()));
    $this->ciJob
      ->script($this->ciRunOptions->reveal())
      ->shouldBeCalledOnce();
    $this->ciJob
      ->exitEarly()
      ->shouldBeCalled();

    $this->executeCommand([
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ]);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testInvalidOptions(): void {
    $this->ciRunOptionsFactory
      ->create(Argument::any())
      ->willThrow(OrcaInvalidArgumentException::class);
    $this->executeCommand([
      'job' => 'invalid',
      'phase' => 'invalid',
      'sut' => 'invalid',
    ]);

    self::assertStringStartsWith('Error: ', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

}
