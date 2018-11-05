<?php

/**
 * @file
 * ORCA command line front file.
 */

namespace Acquia\Orca;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
  die("Could not find autoloader. Run 'composer install' first.\n");
}
require __DIR__ . '/../vendor/autoload.php';

set_time_limit(0);

const ORCA_VERSION = 'v1.0.0-alpha1';

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], $_SERVER['APP_ENV'] ?? 'prod', TRUE);
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env)) && !$input->hasParameterOption('--no-debug', TRUE);

if ($debug) {
  umask(0000);

  // phpcs:disable MySource.Debug.DebugCode.Found
  if (class_exists(Debug::class)) {
    Debug::enable();
    // phpcs:enable
  }
}

$kernel = new Kernel($env, $debug);
$kernel->boot();
$container = $kernel->getContainer();
$application = $container->get(Application::class);
$application->setName('ORCA');
$application->setVersion(ORCA_VERSION);
$application->run();
