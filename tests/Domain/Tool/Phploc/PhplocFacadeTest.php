<?php

namespace Acquia\Orca\Tests\Domain\Tool\Phploc;

use Acquia\Orca\Domain\Tool\Phploc\PhplocFacade;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Tool\Phploc\PhplocFacade
 */
class PhplocFacadeTest extends TestCase {

  protected OrcaPathHandler|ObjectProphecy $orca;
  protected ProcessRunner|ObjectProphecy $processRunner;

  protected function setUp(): void {
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any(), Argument::any())
      ->willReturn(0);
  }

  private function createPhploc(): PhplocFacade {
    $orca_path_handler = $this->orca->reveal();
    $process_runner = $this->processRunner->reveal();
    return new PhplocFacade($orca_path_handler, $process_runner);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($path): void {
    $phploc = $this->createPhploc();
    $this->processRunner
      ->runOrcaVendorBin([
        'phploc',
        '--exclude=tests',
        '--exclude=var',
        '--exclude=vendor',
        '--exclude=docroot',
        '--log-json=' . PhplocFacade::JSON_LOG_PATH,
        '--suffix=.php',
        '--suffix=.module',
        '--suffix=.theme',
        '--suffix=.inc',
        '--suffix=.install',
        '--suffix=.profile',
        '--suffix=.engine',
        '.',
      ], $path)
      ->shouldBeCalledOnce();

    $phploc->execute($path);
  }

  public static function providerCommand(): array {
    return [
      ['/var/www'],
      ['/test/example'],
    ];
  }

}
