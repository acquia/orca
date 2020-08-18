<?php

namespace Acquia\Orca\Tests\Facade;

use Acquia\Orca\Facade\PhplocFacade;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Facade\PhplocFacade
 */
class PhplocFacadeTest extends TestCase {

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

  private function createPhplocFacade(): PhplocFacade {
    $orca_path_handler = $this->orca->reveal();
    $process_runner = $this->processRunner->reveal();
    return new PhplocFacade($orca_path_handler, $process_runner);
  }

  /**
   * @dataProvider dataProviderCommand
   */
  public function testCommand($path): void {
    $phploc = $this->createPhplocFacade();
    $this->processRunner
      ->runOrcaVendorBin([
        'phploc',
        '--names=*.php,*.module,*.theme,*.inc,*.install,*.profile,*.engine',
        '--exclude=tests',
        '--exclude=var',
        '--exclude=vendor',
        '--exclude=docroot',
        '--log-json=' . PhplocFacade::JSON_LOG_PATH,
        '.',
      ], $path)
      ->shouldBeCalledOnce();

    $phploc->execute($path);
  }

  public function dataProviderCommand(): array {
    return [
      ['/var/www'],
      ['/test/example'],
    ];
  }

}
