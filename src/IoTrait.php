<?php

namespace Acquia\Orca;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides an I/O object.
 */
trait IoTrait {

  /**
   * The I/O object.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $io;

  /**
   * Returns the I/O object.
   *
   * @return \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected function io(): SymfonyStyle {
    if (!$this->io) {
      $this->io = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());
    }
    return $this->io;
  }

}
