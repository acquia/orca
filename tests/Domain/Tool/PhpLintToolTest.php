<?php

namespace Acquia\Orca\Tests\Domain\Tool;

use Acquia\Orca\Domain\Tool\PhpLintTool;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Tool\PhpLintTool
 */
class PhpLintToolTest extends TestCase {

  private const PATH = '/var/www/sut';

  protected function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::PATH)
      ->willReturn(0);
  }

  private function createPhpLintTool(): PhpLintTool {
    $process_runner = $this->processRunner->reveal();
    return new PhpLintTool($process_runner);
  }

  /**
   * @covers ::__construct
   * @covers ::label
   * @covers ::statusMessage
   */
  public function testBasicConfiguration(): void {
    $tool = $this->createPhpLintTool();

    self::assertNotEmpty($tool->label(), 'Provided a label.');
    self::assertNotEmpty($tool->statusMessage(), 'Provided a status message.');
  }

  /**
   * @covers ::run
   */
  public function testRunSuccess(): void {
    $this->processRunner
      ->runOrcaVendorBin([
        'parallel-lint',
        '-e',
        'inc,install,module,php,profile,test,theme',
        '--exclude',
        'vendor',
        '--colors',
        '--blame',
        '.',
      ], self::PATH)
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $tool = $this->createPhpLintTool();

    $tool->run(self::PATH);
  }

  public function testRunFailure(): void {
    $process = $this->prophesize(Process::class);
    $process->getExitCode()
      ->willReturn(0);
    $exception = $this->prophesize(ProcessFailedException::class);
    $exception->getProcess()
      ->willReturn($process->reveal());
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::PATH)
      ->willThrow($exception->reveal());
    $this->expectException(OrcaTaskFailureException::class);
    $tool = $this->createPhpLintTool();

    $tool->run(self::PATH);
  }

  public function testRunNoFilesFound(): void {
    $process = $this->prophesize(Process::class);
    $process->getExitCode()
      ->willReturn(254);
    $exception = $this->prophesize(ProcessFailedException::class);
    $exception->getProcess()
      ->willReturn($process->reveal());
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::PATH)
      ->shouldBeCalledOnce()
      ->willThrow($exception->reveal());
    $tool = $this->createPhpLintTool();

    $tool->run(self::PATH);
  }

  public function testRunUnexpectedException(): void {
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::PATH)
      ->willThrow(\Exception::class);
    $this->expectException(\Exception::class);
    $tool = $this->createPhpLintTool();

    $tool->run(self::PATH);
  }

}
