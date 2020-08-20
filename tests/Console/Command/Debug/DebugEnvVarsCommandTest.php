<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugEnvVarsCommand;
use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

class DebugEnvVarsCommandTest extends CommandTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createCommand(): Command {
    return new DebugEnvVarsCommand();
  }

  public function testBasicExecution(): void {
    $this->executeCommand();

    self::assertContains('+--', $this->getDisplay());
    self::assertContains('| Variable ', $this->getDisplay());
    self::assertContains('| Value ', $this->getDisplay());
    self::assertContains('| ORCA_', $this->getDisplay());
    self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

}
