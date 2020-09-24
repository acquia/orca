<?php

namespace Acquia\Orca\Console\Helper;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a table for status displays.
 */
class StatusTable extends Table {

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   */
  public function __construct(OutputInterface $output) {
    parent::__construct($output);
    $this->setStyle($this->tableStyle());
  }

  /**
   * Provides the table style.
   *
   * @return \Symfony\Component\Console\Helper\TableStyle
   *   A TableStyle object.
   */
  private function tableStyle(): TableStyle {
    return (new TableStyle())
      ->setHorizontalBorderChars('', '-')
      ->setVerticalBorderChars('', ':')
      ->setDefaultCrossingChar('');
  }

}
