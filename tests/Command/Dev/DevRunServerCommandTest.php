<?php

namespace Acquia\Orca\Tests\Command\Dev;

use Acquia\Orca\Command\Dev\DevRunServerCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Server\WebServer;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\WebServer $webServer
 */
class DevRunServerCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->fixture->getPath('docroot')
      ->willReturn(self::FIXTURE_DOCROOT);
    $this->webServer = $this->prophesize(WebServer::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $methods_called, $status_code, $display) {
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
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, DevRunServerCommand::getDefaultName());

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, ['exists'], StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, ['exists', 'start', 'wait'], StatusCodes::OK, sprintf("Starting web server...\nListening on http://%s.\nDocument root is %s.\nPress Ctrl-C to quit.\n", Fixture::WEB_ADDRESS, self::FIXTURE_DOCROOT)],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Server\WebServer $web_server */
    $web_server = $this->webServer->reveal();
    $application->add(new DevRunServerCommand($fixture, $web_server));
    /** @var \Acquia\Orca\Command\Fixture\FixtureInitCommand $command */
    $command = $application->find(DevRunServerCommand::getDefaultName());
    $this->assertInstanceOf(DevRunServerCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
