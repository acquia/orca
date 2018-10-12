<?php

namespace Acquia\Orca;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Manages an environment made of bundles.
 */
class Kernel extends BaseKernel {

  use MicroKernelTrait;

  const CONFIG_EXTS = '.{php,xml,yaml,yml}';

  /**
   * {@inheritdoc}
   */
  public function getCacheDir() {
    return $this->getProjectDir() . '/var/cache/' . $this->environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogDir() {
    return $this->getProjectDir() . '/var/log';
  }

  /**
   * {@inheritdoc}
   */
  public function registerBundles() {
    $contents = require $this->getProjectDir() . '/config/bundles.php';
    foreach ($contents as $class => $envs) {
      if (isset($envs['all']) || isset($envs[$this->environment])) {
        yield new $class();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader) {
    $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
    $container->setParameter('container.dumper.inline_class_loader', TRUE);
    $confDir = $this->getProjectDir() . '/config';

    $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
    $loader->load($confDir . '/{packages}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
    $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
    $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
  }

  /**
   * {@inheritdoc}
   */
  protected function configureRoutes(RouteCollectionBuilder $routes) {
    $confDir = $this->getProjectDir() . '/config';

    $routes->import($confDir . '/{routes}/*' . self::CONFIG_EXTS, '/', 'glob');
    $routes->import($confDir . '/{routes}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, '/', 'glob');
    $routes->import($confDir . '/{routes}' . self::CONFIG_EXTS, '/', 'glob');
  }

}
