<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureRunServerCommand;
use Acquia\Orca\Domain\Server\WebServer;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Server\WebServer $webServer
 */
class FixtureRunServerCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->fixture->getPath('docroot')
      ->willReturn(self::FIXTURE_DOCROOT);
    $this->webServer = $this->prophesize(WebServer::class);
  }

  protected function createCommand(): Command {
    $fixture = $this->fixture->reveal();
    $web_server = $this->webServer->reveal();
    return new FixtureRunServerCommand($fixture, $web_server);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $methods_called, $status_code, $display): void {
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->webServer
      ->start()
      ->shouldBeCalledTimes((int) in_array('start', $methods_called));
    $this->webServer
      ->wait()
      ->shouldBeCalledTimes((int) in_array('wait', $methods_called));

    $this->executeCommand();

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [FALSE, ['exists'], StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, ['exists', 'start', 'wait'], StatusCodeEnum::OK, sprintf("Starting web server...\nListening on http://%s.\nDocument root is %s.\nPress Ctrl-C to quit.\n", WebServer::WEB_ADDRESS, self::FIXTURE_DOCROOT)],
    ];
  }

}
