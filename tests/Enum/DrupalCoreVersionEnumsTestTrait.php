<?php

namespace Acquia\Orca\Tests\Enum;

use Acquia\Orca\Enum\DrupalCoreVersionEnum;

trait DrupalCoreVersionEnumsTestTrait {

  public static function providerVersions(): array {
    $versions = DrupalCoreVersionEnum::values();
    array_walk($versions, static function (&$value) {
      $value = [$value];
    });
    return $versions;
  }

  protected function validVersion(): DrupalCoreVersionEnum {
    $versions = DrupalCoreVersionEnum::values();
    return reset($versions);
  }

}
