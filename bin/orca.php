<?php

/**
 * @file
 * ORCA command line front file.
 */

namespace Acquia\Orca;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
  die("Could not find autoloader. Run 'composer install' first.\n");
}
require __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', -1);
set_time_limit(0);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container = new ContainerBuilder();

$loader = new YamlFileLoader($container, new FileLocator());
$loader->load(__DIR__ . '/../config/services.yml');

$container->setParameter('app.project_dir', dirname(__DIR__));
$container->setParameter('app.fixture_dir', dirname(__DIR__) . '/../orca-build');

$container->compile();

/**
 * Console application class.
 *
 * @var \Symfony\Component\Console\Application $application
 */
$application = $container->get(Application::class);

// Register commands.
foreach ($container->getServiceIds() as $serviceId) {
  $service = $container->get($serviceId);
  if ($service instanceof Command) {
    $application->add($service);
  }
}

return $application->run();
