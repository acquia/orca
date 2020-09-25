<?php

namespace Acquia\Orca\Tests\Domain\Tool;

use Acquia\Orca\Domain\Tool\PhpmdTool;
use Acquia\Orca\Helper\Config\ConfigFileOverrider;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Helper\Config\ConfigFileOverrider|\Prophecy\Prophecy\ObjectProphecy $configFileOverrider
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Tool\PhpmdTool
 */
class PhpmdToolTest extends TestCase {

  private const PATH = '/var/www/sut';

  protected function setUp(): void {
    $this->configFileOverrider = $this->prophesize(ConfigFileOverrider::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  private function createPhpmdTool(): PhpmdTool {
    $config_file_overrider = $this->configFileOverrider->reveal();
    $orca = $this->orca->reveal();
    $process_runner = $this->processRunner->reveal();
    return new PhpmdTool($config_file_overrider, $orca, $process_runner);
  }

  /**
   * @covers ::__construct
   * @covers ::label
   * @covers ::statusMessage
   */
  public function testBasicConfiguration(): void {
    $tool = $this->createPhpmdTool();

    self::assertNotEmpty($tool->label(), 'Provided a label.');
    self::assertNotEmpty($tool->statusMessage(), 'Provided a status message.');
  }

  /**
   * @covers ::overrideConfig
   * @covers ::restoreConfig
   * @covers ::run
   * @covers ::runPhpmd
   */
  public function testRun(): void {
    $this->configFileOverrider
      ->setPaths(
        'phpmd.xml.dist',
        '/var/www/sut/phpmd.xml'
      )
      ->shouldBeCalledOnce();
    $this->configFileOverrider
      ->override()
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runOrcaVendorBin([
        'phpmd',
        '.',
        'text',
        'phpmd.xml.dist',
        '--ignore-violations-on-exit',
      ], self::PATH)
      ->shouldBeCalledOnce();
    $this->configFileOverrider
      ->restore()
      ->shouldBeCalledOnce();
    $tool = $this->createPhpmdTool();

    $tool->run(self::PATH);
  }

}
