<?php

/**
 * @file
 * ORCA command line front file.
 */

namespace Acquia\Orca;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Filesystem\Filesystem;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
  die("Could not find autoloader. Run 'composer install' first.\n");
}
require __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', -1);
set_time_limit(0);

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], $_SERVER['APP_ENV'] ?? 'prod', TRUE);

$kernel = new Kernel($env, FALSE);

// Handle a cache:clear pseudo command. This isn't implemented as a true console
// command because a stale or corrupted cache would render it unusable--
// precisely when it is needed.
if (in_array($input->getFirstArgument(), ['cache:clear', 'cc'])) {
  $filesystem = new Filesystem();
  // Delete cache directory.
  $cache_dir = $kernel->getCacheDir();
  $filesystem->remove($cache_dir);
  $filesystem->mkdir($cache_dir);
  $filesystem->touch("{$cache_dir}/.gitkeep");
  // Delete coverage report directory.
  $coverage_report_dir = __DIR__ . '/../var/coverage-report';
  $filesystem->remove($coverage_report_dir);
  $filesystem->mkdir($coverage_report_dir);
  $filesystem->touch("{$coverage_report_dir}/.gitkeep");
  exit;
}

$kernel->boot();
$container = $kernel->getContainer();
$application = $container->get(Application::class);
$application->setName('ORCA');
$application->setVersion(trim(file_get_contents(__DIR__ . '/../config/VERSION')));

// Add command autocompletion.
$application->add(new CompletionCommand());

$application->run();
echo "return2";
