<?php

namespace Acquia\Orca\Tests\Console\Command\Debug;

use Acquia\Orca\Console\Command\Debug\DebugEnvVarsCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

class DebugEnvVarsCommandTest extends CommandTestBase {

  public function testBasicExecution() {
    $this->executeCommand();

    $this->assertContains('+--', $this->getDisplay());
    $this->assertContains('| Variable ', $this->getDisplay());
    $this->assertContains('| Value ', $this->getDisplay());
    $this->assertContains('| ORCA_', $this->getDisplay());
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * {@inheritdoc}
   */
  protected function createCommand(): Command {
    return new DebugEnvVarsCommand();
  }

}
