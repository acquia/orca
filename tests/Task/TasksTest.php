<?php

namespace Acquia\Orca\Tests\Task;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Task\PhpcsConfigurator;
use Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask;
use Acquia\Orca\Task\TestFramework\PhpUnitTask;
use Acquia\Orca\Utility\ConfigFileOverrider;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class TasksTest extends TestCase {

  /**
   * @dataProvider providerConstruction
   */
  public function testConstruction($class) {
    $config_file_overrider = $this->prophesize(ConfigFileOverrider::class)->reveal();
    $filesystem = $this->prophesize(Filesystem::class)->reveal();
    $fixture = $this->prophesize(FixturePathHandler::class)->reveal();
    $orca_path_handler = $this->prophesize(OrcaPathHandler::class)->reveal();
    $output = $this->prophesize(SymfonyStyle::class)->reveal();
    $phpcs_configurator = $this->prophesize(PhpcsConfigurator::class)->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $object = new $class($config_file_overrider, $filesystem, $fixture, $orca_path_handler, $output, $phpcs_configurator, $process_runner);

    self::assertInstanceOf($class, $object, sprintf('Successfully instantiated class: %s.', $class));
  }

  public function providerConstruction() {
    return [
      [ComposerValidateTask::class],
      [PhpCodeSnifferTask::class],
      [PhpLintTask::class],
      [PhpMessDetectorTask::class],
      [PhpUnitTask::class],
    ];
  }

}
