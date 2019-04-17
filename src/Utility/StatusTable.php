<?php

namespace Acquia\Orca\Utility;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a table for status displays.
 */
class StatusTable extends Table {

  /**
   * {@inheritdoc}
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
