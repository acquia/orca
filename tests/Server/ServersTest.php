<?php

namespace Acquia\Orca\Tests\Server;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Server\ChromeDriverServer;
use Acquia\Orca\Server\WebServer;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;

class ServersTest extends TestCase {

  public function testConstruction() {
    $fixture = $this->prophesize(FixturePathHandler::class)->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    $orca_path_handler = $this->prophesize(OrcaPathHandler::class)->reveal();

    $chrome_driver_server = new ChromeDriverServer($fixture, $orca_path_handler, $process_runner);
    $web_server = new WebServer($fixture, $process_runner);

    $this->assertInstanceOf(ChromeDriverServer::class, $chrome_driver_server, 'Instantiated ChromeDriverServer class.');
    $this->assertInstanceOf(WebServer::class, $web_server, 'Instantiated WebServer class.');
  }

}
