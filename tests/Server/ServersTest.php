<?php

namespace Acquia\Orca\Tests\Server;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Server\ChromeDriverServer;
use Acquia\Orca\Server\WebServer;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;

class ServersTest extends TestCase {

  public function testConstruction() {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->prophesize(Fixture::class)->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $chrome_driver_server = new ChromeDriverServer($fixture, $process_runner, '');
    $web_server = new WebServer($fixture, $process_runner);

    $this->assertInstanceOf(ChromeDriverServer::class, $chrome_driver_server, 'Instantiated ChromeDriverServer class.');
    $this->assertInstanceOf(WebServer::class, $web_server, 'Instantiated WebServer class.');
  }

}
