<?php

namespace Acquia\Orca\Tests\Domain\Server;

use Acquia\Orca\Domain\Server\ChromeDriverServer;
use Acquia\Orca\Domain\Server\WebServer;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;

class ServersTest extends TestCase {

  public function testConstruction(): void {
    $fixture = $this->prophesize(FixturePathHandler::class)->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();
    $orca_path_handler = $this->prophesize(OrcaPathHandler::class)->reveal();

    $chrome_driver_server = new ChromeDriverServer($fixture, $orca_path_handler, $process_runner);
    $web_server = new WebServer($fixture, $process_runner);

    self::assertInstanceOf(ChromeDriverServer::class, $chrome_driver_server, 'Instantiated ChromeDriverServer class.');
    self::assertInstanceOf(WebServer::class, $web_server, 'Instantiated WebServer class.');
  }

}
