<?php

namespace Acquia\Orca\Domain\Server;

use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Provides a ChromeDriver server.
 */
class ChromeDriverServer extends ServerBase {

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, ProcessRunner $process_runner) {
    parent::__construct($fixture_path_handler, $process_runner);
    $this->orca = $orca_path_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function createProcess(): Process {
    $command = [
      $this->orca->getPath('vendor/bin/chromedriver'),
      '--disable-dev-shm-usage',
      '--disable-extensions',
      '--disable-gpu',
      '--headless',
      '--no-sandbox',
      '--port=4444',
    ];
    return new Process($command);
  }

}
