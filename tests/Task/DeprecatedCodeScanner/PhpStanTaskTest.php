<?php

namespace Acquia\Orca\Tests\Task\DeprecatedCodeScanner;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Task\DeprecatedCodeScanner\PhpStanTask;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ProcessRunner $processRunner
 */
class PhpStanTaskTest extends TestCase {

  public function testTaskRunner() {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->prophesize(Filesystem::class)->reveal();
    $fixture = $this->prophesize(FixturePathHandler::class)->reveal();
    $orca_path_handler = $this->prophesize(OrcaPathHandler::class)->reveal();
    /** @var \Symfony\Component\Console\Style\SymfonyStyle $output */
    $output = $this->prophesize(SymfonyStyle::class)->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->prophesize(PackageManager::class)->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    /** @var \Acquia\Orca\Log\TelemetryClient $telemetry_client */
    $telemetry_client = $this->prophesize(TelemetryClient::class)->reveal();

    $task = new PhpStanTask($filesystem, $fixture, $orca_path_handler, $output, $package_manager, $process_runner, $telemetry_client);

    $this->assertInstanceOf(PhpStanTask::class, $task, 'Instantiated class.');
  }

}
