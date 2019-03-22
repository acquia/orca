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
      ->createExecutableProcess([
        'php',
        '-S',
        Fixture::WEB_ADDRESS,
        $this->getFixture()->getPath('vendor/drush/drush/misc/d8-rs-router.php'),
      ])
      ->setWorkingDirectory($docroot);
  }

}
