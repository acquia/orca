<?php

namespace Acquia\Orca\Tests\Facade;

use Acquia\Orca\Facade\ComposerFacade;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class ComposerFacadeTest extends TestCase {

  protected function setUp() {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  public function createComposerFacade(): ComposerFacade {
    $process_runner = $this->processRunner->reveal();
    return new ComposerFacade($process_runner);
  }

  /**
   * @dataProvider providerCreateProject
   */
  public function testCreateProject(string $project_template_string, string $stability, string $directory): void {
    /** @var array $any_argument */
    $any_argument = Argument::any();
    $this->processRunner
      ->runOrcaVendorBin($any_argument)
      ->willReturn(0);
    $this->processRunner
      ->runOrcaVendorBin([
        'composer',
        'create-project',
        '--no-dev',
        '--no-scripts',
        '--no-install',
        '--no-interaction',
        "--stability={$stability}",
        $project_template_string,
        $directory,
      ])
      ->shouldBeCalledOnce();

    $composer = $this->createComposerFacade();
    $composer->createProject($project_template_string, $stability, $directory);
  }

  public function providerCreateProject() {
    return [
      ['test/example-project1', 'alpha', '/var/www/orca-build1'],
      ['test/example-project2', 'dev', '/var/www/orca-build2'],
    ];
  }

}
