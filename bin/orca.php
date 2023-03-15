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

use Acquia\Orca\Event\TelemetrySubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;

$container = new ContainerBuilder();

$loader = new YamlFileLoader($container, new FileLocator());
$loader->load(__DIR__ . '/../config/services.yml');

$container->setParameter('app.project_dir', dirname(__DIR__));
$container->setParameter('app.fixture_dir', dirname(__DIR__) . '/../orca-build');

$container->register('telemetry.service', TelemetrySubscriber::class);

$container->compile(TRUE);

$application = $container->get(Application::class);
$application->setName('ORCA');
$application->setVersion(trim(file_get_contents(__DIR__ . '/../config/VERSION')));

// Register commands.
foreach ($container->getServiceIds() as $serviceId) {
  $service = $container->get($serviceId);
  if ($service instanceof Command) {
    $application->add($service);
  }

  if ($service instanceof EventDispatcher) {

    $telemetrySubscriber = $container->get('Acquia\Orca\Event\TelemetrySubscriber');
    $service->addSubscriber($telemetrySubscriber);
    $application->setDispatcher($service);
  }
}

return $application->run();
