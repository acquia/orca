<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\RunServerCommand;
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
class RunServerCommandTest extends CommandTestBase {

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
  public function testCommand($fixture_exists, $methods_called, $inputs, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->webServer
      ->start()
      ->shouldBeCalledTimes((int) in_array('start', $methods_called));
    $this->webServer
      ->stop()
      ->shouldBeCalledTimes((int) in_array('stop', $methods_called));
    $tester = $this->createCommandTester();
    $tester->setInputs($inputs);

    $this->executeCommand($tester, RunServerCommand::getDefaultName());

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, ['exists'], [], StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, ['exists', 'start', 'stop'], ['x'], StatusCodes::OK, sprintf("Web server started.\nListening on http://%s.\nDocument root is %s.\nPress ENTER to quit.\n", Fixture::WEB_ADDRESS, self::FIXTURE_DOCROOT)],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Server\WebServer $web_server */
    $web_server = $this->webServer->reveal();
    $application->add(new RunServerCommand($fixture, $web_server));
    /** @var \Acquia\Orca\Command\Fixture\InitCommand $command */
    $command = $application->find(RunServerCommand::getDefaultName());
    $this->assertInstanceOf(RunServerCommand::class, $command, 'Successfully instantiated class.');
    return new CommandTester($command);
  }

}
