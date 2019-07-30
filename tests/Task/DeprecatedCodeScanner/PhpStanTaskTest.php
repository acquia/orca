<?php

namespace Acquia\Orca\Tests\Task\DeprecatedCodeScanner;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Task\DeprecatedCodeScanner\PhpStanTask;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ProcessRunner $processRunner
 */
class PhpStanTaskTest extends TestCase {

  public function testTaskRunner() {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->prophesize(Filesystem::class)->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->prophesize(Fixture::class)->reveal();
    /** @var \Symfony\Component\Console\Style\SymfonyStyle $output */
    $output = $this->prophesize(SymfonyStyle::class)->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->prophesize(PackageManager::class)->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    $project_dir = '/var/www/orca';
    /** @var \Acquia\Orca\Log\TelemetryClient $telemetry_client */
    $telemetry_client = $this->prophesize(TelemetryClient::class)->reveal();

    $task = new PhpStanTask($filesystem, $fixture, $output, $package_manager, $process_runner, $project_dir, $telemetry_client);

    $this->assertInstanceOf(PhpStanTask::class, $task, 'Instantiated class.');
  }

}
