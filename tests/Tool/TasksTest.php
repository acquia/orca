<?php

namespace Acquia\Orca\Tests\Tool;

use Acquia\Orca\Helper\Config\ConfigFileOverrider;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tool\ComposerValidate\ComposerValidateTask;
use Acquia\Orca\Tool\Phpcs\PhpcsConfigurator;
use Acquia\Orca\Tool\Phpcs\PhpcsTask;
use Acquia\Orca\Tool\PhpLint\PhpLintTask;
use Acquia\Orca\Tool\Phpmd\PhpmdTask;
use Acquia\Orca\Tool\Phpunit\PhpUnitTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class TasksTest extends TestCase {

  /**
   * @dataProvider providerConstruction
   */
  public function testConstruction($class): void {
    $clover_coverage = '/var/coverage/clover.xml';
    $config_file_overrider = $this->prophesize(ConfigFileOverrider::class)->reveal();
    $filesystem = $this->prophesize(Filesystem::class)->reveal();
    $fixture = $this->prophesize(FixturePathHandler::class)->reveal();
    $orca_path_handler = $this->prophesize(OrcaPathHandler::class)->reveal();
    $output = $this->prophesize(SymfonyStyle::class)->reveal();
    $phpcs_configurator = $this->prophesize(PhpcsConfigurator::class)->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $object = new $class($clover_coverage, $config_file_overrider, $filesystem, $fixture, $orca_path_handler, $output, $phpcs_configurator, $process_runner);

    self::assertInstanceOf($class, $object, sprintf('Successfully instantiated class: %s.', $class));
  }

  public function providerConstruction(): array {
    return [
      [ComposerValidateTask::class],
      [PhpcsTask::class],
      [PhpLintTask::class],
      [PhpmdTask::class],
      [PhpUnitTask::class],
    ];
  }

}
