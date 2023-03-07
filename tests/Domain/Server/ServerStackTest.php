<?php

namespace Acquia\Orca\Tests\Domain\Server;

use Acquia\Orca\Domain\Server\ChromeDriverServer;
use Acquia\Orca\Domain\Server\ProcessOutputCallback;
use Acquia\Orca\Domain\Server\ServerStack;
use Acquia\Orca\Domain\Server\WebServer;
use Acquia\Orca\Helper\Clock;
use Acquia\Orca\Tests\TestCase;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Server\ChromeDriverServer $chromeDriverServer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Clock $clock
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Server\WebServer $webServer
 */
class ServerStackTest extends TestCase {

  protected function setUp(): void {
    $this->chromeDriverServer = $this->prophesize(ChromeDriverServer::class);
    $this->clock = $this->prophesize(Clock::class);
    $this->webServer = $this->prophesize(WebServer::class);
  }

  protected function createServerStack(): ServerStack {
    $chrome_driver_server = $this->chromeDriverServer->reveal();
    $clock = $this->clock->reveal();
    $web_server = $this->webServer->reveal();
    return new ServerStack($chrome_driver_server, $clock, $web_server);
  }

  public function testServerStack(): void {
    $callback = $this->prophesize(ProcessOutputCallback::class)->reveal();

    $this->webServer
      ->start($callback)
      ->shouldBeCalledTimes(1);
    $this->chromeDriverServer
      ->start($callback)
      ->shouldBeCalledTimes(1);
    $this->webServer
      ->stop()
      ->shouldBeCalledTimes(1);
    $this->chromeDriverServer
      ->stop()
      ->shouldBeCalledTimes(1);

    $servers = $this->createServerStack();
    $servers->start($callback);
    $servers->stop();

    self::assertInstanceOf(ServerStack::class, $servers, 'Instantiated class.');
  }

}
