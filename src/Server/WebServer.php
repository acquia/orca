<?php

namespace Acquia\Orca\Server;

use Symfony\Component\Process\Process;

/**
 * Provides a web server.
 */
class WebServer extends ServerBase {

  public const WEB_ADDRESS = '127.0.0.1:8080';

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
        self::WEB_ADDRESS,
        $this->getFixture()->getPath('vendor/drush/drush/misc/d8-rs-router.php'),
      ])
      ->setWorkingDirectory($docroot);
  }

}
