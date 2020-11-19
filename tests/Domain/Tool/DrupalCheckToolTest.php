<?php

namespace Acquia\Orca\Tests\Domain\Tool;

use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Tool\DrupalCheckTool;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Log\TelemetryClient;
use Acquia\Orca\Helper\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Helper\Log\TelemetryClient|\Prophecy\Prophecy\ObjectProphecy $telemetryClient
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Symfony\Component\Console\Style\SymfonyStyle|\Prophecy\Prophecy\ObjectProphecy $symfonyStyle
 * @property \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
class DrupalCheckToolTest extends TestCase {

  private const FIXTURE_PATH = '/var/www/orca-build';

  private const PACKAGE_NAME = 'drupal/example';

  private const PACKAGE_PATH = 'docroot/modules/contrib/example';

  protected function setUp(): void {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->getPath(NULL)
      ->willReturn(self::FIXTURE_PATH);
    $this->fixture
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(Argument::any())
      ->willReturnArgument();
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->get(self::PACKAGE_NAME)
      ->willReturn($this->createPackage([], self::PACKAGE_NAME));
    $this->symfonyStyle = $this->prophesize(SymfonyStyle::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrcaVendorBin(Argument::any())
      ->willReturn(0);
    $this->telemetryClient = $this->prophesize(TelemetryClient::class);
    $this->telemetryClient
      ->isReady()
      ->willReturn(FALSE);
  }

  private function createDrupalCheckTool(): DrupalCheckTool {
    $filesystem = $this->filesystem->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    $output = $this->symfonyStyle->reveal();
    $package_manager = $this->packageManager->reveal();
    $process_runner = $this->processRunner->reveal();
    $telemetry_client = $this->telemetryClient->reveal();
    return new DrupalCheckTool($filesystem, $fixture_path_handler, $orca_path_handler, $output, $package_manager, $process_runner, $telemetry_client);
  }

  private function createPackage($data, $package_name): Package {
    $fixture_path_handler = $this->fixture->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new Package($data, $fixture_path_handler, $orca_path_handler, $package_name);
  }

  public function testRunWithSut(): void {
    $this->filesystem
      ->mkdir(Argument::any())
      ->shouldNotBeCalled();
    $this->processRunner
      ->runOrcaVendorBin([
        'drupal-check',
        '-d',
        sprintf('--drupal-root=%s', self::FIXTURE_PATH),
        self::PACKAGE_PATH,
      ])
      ->shouldBeCalledOnce();

    $tool = $this->createDrupalCheckTool();
    $tool->run(self::PACKAGE_NAME, FALSE);
  }

  public function testRunWithContrib(): void {
    $this->filesystem
      ->mkdir(Argument::any())
      ->shouldBeCalledTimes(4);
    $this->processRunner
      ->runOrcaVendorBin([
        'drupal-check',
        '-d',
        sprintf('--drupal-root=%s', self::FIXTURE_PATH),
        'docroot/modules/contrib',
        'docroot/profiles/contrib',
        'docroot/themes/contrib',
        'vendor/acquia',
      ])
      ->shouldBeCalledOnce();

    $tool = $this->createDrupalCheckTool();
    $status = $tool->run(NULL, TRUE);

    self::assertSame(StatusCodeEnum::OK, $status, 'Returned correct status code');
  }

  public function testRunWithSutAndContrib(): void {
    $this->filesystem
      ->mkdir(Argument::any())
      ->shouldBeCalledTimes(4);
    $this->processRunner
      ->runOrcaVendorBin([
        'drupal-check',
        '-d',
        sprintf('--drupal-root=%s', self::FIXTURE_PATH),
        self::PACKAGE_PATH,
        'docroot/modules/contrib',
        'docroot/profiles/contrib',
        'docroot/themes/contrib',
        'vendor/acquia',
      ])
      ->shouldBeCalledOnce();

    $tool = $this->createDrupalCheckTool();
    $status = $tool->run(self::PACKAGE_NAME, TRUE);

    self::assertSame(StatusCodeEnum::OK, $status, 'Returned correct status code');
  }

  public function testRunFailure(): void {
    $this->processRunner
      ->runOrcaVendorBin(Argument::any())
      ->willThrow(ProcessFailedException::class);

    $tool = $this->createDrupalCheckTool();
    $status = $tool->run(NULL, TRUE);

    self::assertSame(StatusCodeEnum::ERROR, $status, 'Returned correct status code');
  }

  public function testRunLogResult(): void {
    $this->telemetryClient
      ->isReady()
      ->willReturn(TRUE);
    $this->symfonyStyle
      ->comment(Argument::any())
      ->shouldBeCalledOnce();
    $this->filesystem
      ->remove(DrupalCheckTool::JSON_LOG_PATH)
      ->shouldBeCalledOnce();
    $process = $this->prophesize(Process::class);
    $process->setWorkingDirectory(self::FIXTURE_PATH)
      ->shouldBeCalledOnce();
    $process->run()
      ->shouldBeCalledOnce();
    $process
      ->getOutput()
      ->willReturn('  EXAMPLE OUTPUT  ');
    $this->processRunner
      ->createOrcaVendorBinProcess([
        'drupal-check',
        '-d',
        sprintf('--drupal-root=%s', self::FIXTURE_PATH),
        self::PACKAGE_PATH,
        '--format=json',
      ])
      ->shouldBeCalledOnce()
      ->willReturn($process);
    $this->filesystem
      ->dumpFile(DrupalCheckTool::JSON_LOG_PATH, 'EXAMPLE OUTPUT')
      ->shouldBeCalledOnce();

    $tool = $this->createDrupalCheckTool();
    $tool->run(self::PACKAGE_NAME, FALSE);
  }

  public function testRunDoNotLogResult(): void {
    $this->telemetryClient
      ->isReady()
      ->willReturn(FALSE);
    $this->filesystem
      ->dumpFile(Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    $tool = $this->createDrupalCheckTool();
    $tool->run(self::PACKAGE_NAME, FALSE);
  }

}
