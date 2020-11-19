<?php

/**
 * @file
 * Phan configuration.
 */

return [
  'target_php_version' => NULL,

  'directory_list' => [
    'src',
    'vendor/composer/composer',
    'vendor/composer/semver',
    'vendor/myclabs/php-enum',
    'vendor/hassankhan/config',
    'vendor/oscarotero/env',
    'vendor/symfony/config',
    'vendor/symfony/console',
    'vendor/symfony/dependency-injection',
    'vendor/symfony/filesystem',
    'vendor/symfony/finder',
    'vendor/symfony/http-client-contracts',
    'vendor/symfony/http-kernel',
    'vendor/symfony/options-resolver',
    'vendor/symfony/process',
    'vendor/symfony/yaml',
    'vendor/zumba/amplitude-php',
  ],

  'exclude_file_regex' => '@^vendor/.*/(tests?|Tests?)/@',

  'exclude_analysis_directory_list' => [
    'vendor/',
  ],
];
