<?php

namespace Acquia\Orca\Tests\Tool\Phpstan;

use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Log\TelemetryClient;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Package\PackageManager;
use Acquia\Orca\Tool\Phpstan\PhpstanTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Process\ProcessRunner $processRunner
 */
class PhpStanTaskTest extends TestCase {

  public function testTaskRunner() {
    $filesystem = $this->prophesize(Filesystem::class)->reveal();
    $fixture = $this->prophesize(FixturePathHandler::class)->reveal();
    $orca_path_handler = $this->prophesize(OrcaPathHandler::class)->reveal();
    $output = $this->prophesize(SymfonyStyle::class)->reveal();
    $package_manager = $this->prophesize(PackageManager::class)->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    $telemetry_client = $this->prophesize(TelemetryClient::class)->reveal();

    $task = new PhpstanTask($filesystem, $fixture, $orca_path_handler, $output, $package_manager, $process_runner, $telemetry_client);

    self::assertInstanceOf(PhpstanTask::class, $task, 'Instantiated class.');
  }

}
