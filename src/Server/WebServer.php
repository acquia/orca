<?php

namespace Acquia\Orca\Server;

use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Process\Process;

/**
 * Provides a web server.
 */
class WebServer extends ServerBase {

  /**
   * {@inheritdoc}
   */
  protected function createProcess(): Process {
    $docroot = $this->getFixture()
      ->getPath('docroot');
    return $this->getProcessRunner()
      ->createFixtureVendorBinProcess([
        'drush',
        'runserver',
        Fixture::WEB_ADDRESS,
      ])
      ->setWorkingDirectory($docroot);
  }

}
