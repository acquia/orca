<?php

namespace Acquia\Orca\Tests\Enum\_Helper;

use Acquia\Orca\Enum\EnvVarEnum;

class EnvVarTestEnum extends EnvVarEnum {

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

  public static function toArray(): array {
    return [
      'LOREM' => self::LOREM,
      'IPSUM' => self::IPSUM,
    ];
  }

}
