<?php

namespace Acquia\Orca\Tests\Task\DeprecatedCodeScanner;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Package\PackageManager;
use Acquia\Orca\Task\DeprecatedCodeScanner\PhpStanTask;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ProcessRunner $processRunner
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

    $task = new PhpStanTask($filesystem, $fixture, $orca_path_handler, $output, $package_manager, $process_runner, $telemetry_client);

    $this->assertInstanceOf(PhpStanTask::class, $task, 'Instantiated class.');
  }

}
