<?php

namespace Acquia\Orca\Tests\Server;

use Acquia\Orca\Server\ChromeDriverServer;
use Acquia\Orca\Server\ServerStack;
use Acquia\Orca\Server\WebServer;
use Acquia\Orca\Utility\Clock;
use PHPUnit\Framework\TestCase;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\ChromeDriverServer $chromeDriverServer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\Clock $clock
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\WebServer $webServer
 */
class ServerStackTest extends TestCase {

  public function setUp() {
    $this->chromeDriverServer = $this->prophesize(ChromeDriverServer::class);
    $this->clock = $this->prophesize(Clock::class);
    $this->webServer = $this->prophesize(WebServer::class);
  }

  public function testServerStack() {
    $this->webServer
      ->start()
      ->shouldBeCalledTimes(1);
    $this->chromeDriverServer
      ->start()
      ->shouldBeCalledTimes(1);
    $this->webServer
      ->stop()
      ->shouldBeCalledTimes(1);
    $this->chromeDriverServer
      ->stop()
      ->shouldBeCalledTimes(1);

    $servers = $this->createServerStack();
    $servers->start();
    $servers->stop();

    $this->assertInstanceOf(ServerStack::class, $servers, 'Instantiated class.');
  }

  protected function createServerStack(): ServerStack {
    /** @var \Acquia\Orca\Server\ChromeDriverServer $chrome_driver_server */
    $chrome_driver_server = $this->chromeDriverServer->reveal();
    /** @var \Acquia\Orca\Utility\Clock $clock */
    $clock = $this->clock->reveal();
    /** @var \Acquia\Orca\Server\WebServer $web_server */
    $web_server = $this->webServer->reveal();
    return new ServerStack($chrome_driver_server, $clock, $web_server);
  }

}
