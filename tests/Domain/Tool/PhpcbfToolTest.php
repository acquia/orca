<?php

namespace Acquia\Orca\Tests\Domain\Tool;

use Acquia\Orca\Domain\Tool\PhpcbfTool;
use Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator;
use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @property \Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator|\Prophecy\Prophecy\ObjectProphecy $phpcsConfigurator
 * @property \Acquia\Orca\Enum\PhpcsStandardEnum $phpcsStandard
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Tool\PhpcbfTool
 */
class PhpcbfToolTest extends TestCase {

  private const PATH = '/var/www/sut';

  private const TEMP_DIR = '/tmp/example';

  private $standard = PhpcsStandardEnum::DEFAULT;

  protected function setUp(): void {
    $this->phpcsConfigurator = $this->prophesize(PhpcsConfigurator::class);
    $this->phpcsConfigurator
      ->getTempDir()
      ->willReturn(self::TEMP_DIR);
    $this->phpcsStandard = new PhpcsStandardEnum($this->standard);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  private function createPhpcbfTool(): PhpcbfTool {
    $phpcs_configurator = $this->phpcsConfigurator->reveal();
    $process_runner = $this->processRunner->reveal();
    return new PhpcbfTool($phpcs_configurator, $process_runner);
  }

  /**
   * @covers ::__construct
   * @covers ::label
   * @covers ::statusMessage
   */
  public function testBasicConfiguration(): void {
    $tool = $this->createPhpcbfTool();

    self::assertNotEmpty($tool->label(), 'Provided a label.');
    self::assertNotEmpty($tool->statusMessage(), 'Provided a status message.');
  }

  /**
   * @covers ::run
   * @covers ::setStandard
   */
  public function testRunSuccess(): void {
    $this->phpcsConfigurator
      ->prepareTemporaryConfig($this->phpcsStandard)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runOrcaVendorBin([
        'phpcbf',
        self::PATH,
      ], self::TEMP_DIR)
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $this->phpcsConfigurator
      ->cleanupTemporaryConfig()
      ->shouldBeCalledOnce();
    $tool = $this->createPhpcbfTool();

    $tool->setStandard($this->phpcsStandard);
    $tool->run(self::PATH);
  }

  public function testRunFailure(): void {
    $this->phpcsConfigurator
      ->prepareTemporaryConfig($this->phpcsStandard);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::TEMP_DIR)
      ->willThrow(ProcessFailedException::class);
    $this->expectException(OrcaTaskFailureException::class);
    $this->phpcsConfigurator
      ->cleanupTemporaryConfig()
      ->shouldBeCalledOnce();
    $tool = $this->createPhpcbfTool();

    $tool->run(self::PATH);
  }

  public function testRunUnexpectedException(): void {
    $this->phpcsConfigurator
      ->prepareTemporaryConfig($this->phpcsStandard);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), self::TEMP_DIR)
      ->willThrow(\Exception::class);
    $this->expectException(\Exception::class);
    $this->phpcsConfigurator
      ->cleanupTemporaryConfig()
      ->shouldBeCalledOnce();
    $tool = $this->createPhpcbfTool();

    $tool->run(self::PATH);
  }

}
