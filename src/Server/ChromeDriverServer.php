<?php

namespace Acquia\Orca\Server;

use Symfony\Component\Process\Process;

/**
 * Provides a ChromeDriver server.
 */
class ChromeDriverServer extends ServerBase {

  /**
   * {@inheritdoc}
   */
  protected function createProcess(): Process {
    return $this->getProcessRunner()
      ->createOrcaVendorBinProcess([
        'chromedriver',
        '--port=4444',
      ]);
  }

}
