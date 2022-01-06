<?php

namespace Acquia\Orca\Tests\Domain\Tool;

use Acquia\Orca\Domain\Tool\ComposerValidate\ComposerValidateTask;
use Acquia\Orca\Domain\Tool\PhpcbfTool;
use Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator;
use Acquia\Orca\Domain\Tool\Phpcs\PhpcsTask;
use Acquia\Orca\Domain\Tool\PhpLint\PhpLintTask;
use Acquia\Orca\Domain\Tool\PhpLintTool;
use Acquia\Orca\Domain\Tool\Phpmd\PhpmdTask;
use Acquia\Orca\Domain\Tool\PhpmdTool;
use Acquia\Orca\Domain\Tool\Phpunit\PhpUnitTask;
use Acquia\Orca\Helper\Config\ConfigFileOverrider;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;
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
    $phpcbf_tool = $this->prophesize(PhpcbfTool::class)->reveal();
    $phpcs_configurator = $this->prophesize(PhpcsConfigurator::class)->reveal();
    $php_lint_tool = $this->prophesize(PhpLintTool::class)->reveal();
    $phpmd_tool = $this->prophesize(PhpmdTool::class)->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $object = new $class($clover_coverage, $config_file_overrider, $filesystem, $fixture, $orca_path_handler, $output, $phpcbf_tool, $phpcs_configurator, $php_lint_tool, $phpmd_tool, $process_runner);

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
