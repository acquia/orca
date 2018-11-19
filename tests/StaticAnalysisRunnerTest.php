<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Tasks\ComposerValidateTask;
use Acquia\Orca\Tasks\PhpLintTask;
use PHPUnit\Framework\TestCase;
use Acquia\Orca\StaticAnalysisRunner;

class StaticAnalysisRunnerTest extends TestCase {

  public function testRunner() {
    $path = 'var/www/example';
    $composer_validate = $this->prophesize(ComposerValidateTask::class);
    $composer_validate->setPath($path)
      ->shouldBeCalledTimes(1)
      ->willReturn($composer_validate);
    $composer_validate->execute()->shouldBeCalledTimes(1);
    /** @var \Acquia\Orca\Tasks\ComposerValidateTask $composer_validate */
    $composer_validate = $composer_validate->reveal();
    $php_lint = $this->prophesize(PhpLintTask::class);
    $php_lint->setPath($path)
      ->shouldBeCalled(1)
      ->willReturn($php_lint);
    $php_lint->execute()->shouldBeCalledTimes(1);
    /** @var \Acquia\Orca\Tasks\PhpLintTask $php_lint */
    $php_lint = $php_lint->reveal();

    $runner = new StaticAnalysisRunner($composer_validate, $php_lint);
    $status_code = $runner->run($path);

    $this->assertInstanceOf(StaticAnalysisRunner::class, $runner);
    $this->assertEquals(StatusCodes::OK, $status_code);
  }

}
