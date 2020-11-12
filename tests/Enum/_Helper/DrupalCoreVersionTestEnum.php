<?php

namespace Acquia\Orca\Tests\Enum\_Helper;

use Acquia\Orca\Enum\DrupalCoreVersionEnum;

class DrupalCoreVersionTestEnum extends DrupalCoreVersionEnum {

  public const LOREM = 'LOREM';

  public const IPSUM = 'IPSUM';

  public static function descriptions(): array {
    return [
      self::LOREM => 'Lorem description',
      self::IPSUM => 'Ipsum description',
    ];
  }

  public function getDescription(): string {
    switch ($this->getKey()) {
      case self::LOREM:
        return 'Lorem description';

      case self::IPSUM:
        return 'Ipsum description';
    }
  }

  public function getExample(): string {
    switch ($this->getKey()) {
      case self::LOREM:
        return '1.0.0';

      case self::IPSUM:
        return '2.0.0';
    }
  }

  public static function toArray(): array {
    return [
      'LOREM' => self::LOREM,
      'IPSUM' => self::IPSUM,
    ];
  }

}
