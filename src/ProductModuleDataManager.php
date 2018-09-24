<?php

namespace Acquia\Orca;

/**
 * Manages Acquia product module data.
 */
class ProductModuleDataManager {

  /**
   * Product module data.
   *
   * @var array
   */
  protected static $data = [
    'acquia/acsf-tools' => [
      'version' => '*',
    ],

    'drupal/acquia_commercemanager' => [
      'version' => '*',
      'module' => 'acm',
      'submodules' => [
        // @todo Composer reports that the below commented out submodules
        //   require drupal/acm-acm, which of course cannot be found.
        // 'acm_cart',
        'acm_checkout',
        // 'acm_customer',
        'acm_diagnostic',
        // 'acm_exception',
        // 'acm_payment',
        // 'acm_product',
        // 'acm_promotion',
        // 'acm_sku',
        // 'acm_sku_position',
        // @todo Workaround for the fact that the acm module doesn't declare its
        //   dependency on drupal/pcb in a composer.json file.
        'pcb',
      ],
    ],

    'drupal/acquia_connector' => [
      'version' => '*',
      'module' => 'acquia_connector',
      'submodules' => [
        // @todo Installing the acquia_search module in a non-Acquia hosting
        //   environment causes a PHP fatal error: Class
        //   'Solarium\Core\Plugin\Plugin' not found.
        // 'acquia_search',
      ],
    ],

    'drupal/acquia_contenthub' => [
      'version' => '~1.0',
      'module' => 'acquia_contenthub',
      'submodules' => [
        'acquia_contenthub_diagnostic',
        'acquia_contenthub_status',
        'acquia_contenthub_subscriber',
      ],
    ],

    'drupal/acquia_lift' => [
      'version' => '*',
      'module' => 'acquia_lift',
      'submodules' => [
        'acquia_lift_inspector',
      ],
    ],

    'drupal/acquia_purge' => [
      'version' => '*',
      'module' => 'acquia_purge',
    ],

    'drupal/acsf' => [
      'version' => '*',
      'module' => 'acsf',
      'submodules' => [
        'acsf_duplication',
        'acsf_sso',
        'acsf_theme',
        'acsf_variables',
      ],
    ],

    // @todo The versions of the individual Lightning modules bundles with the
    //   profile need to be removed before these can be require'd, otherwise
    //   "Your requirements could not be resolved to an installable set of
    //   packages."
    // 'drupal/lightning_api' => ['version' => '*'],
    // 'drupal/lightning_core' => ['version' => '*'],
    // 'drupal/lightning_layout' => ['version' => '*'],
    // 'drupal/lightning_media' => ['version' => '*'],
    // 'drupal/lightning_workflow' => ['version' => '*'],

    'drupal/media_acquiadam' => [
      'version' => '*',
      'module' => 'media_acquiadam',
      'submodules' => [
        'lightning_acquiadam',
        // @todo Installing the media_acquiadam_example module causes a
        //   PreExistingConfigException with configuration objects it provides.
        // 'media_acquiadam_example',
        'media_acquiadam_report',
      ],
    ],
  ];

  /**
   * Returns an array of Drupal module names.
   *
   * @return string[]
   */
  public static function moduleNames() {
    $modules = [];
    foreach (self::$data as $package_name => $data) {
      if (!empty($data['module'])) {
        $modules[$data['module']] = $data['module'];
        if (!empty($data['submodules'])) {
          foreach ($data['submodules'] as $submodule) {
            $modules[$submodule] = $submodule;
          }
        }
      }
    }
    return $modules;
  }

  /**
   * Returns an array of Composer package names.
   *
   * @return string[]
   */
  public static function packageNames() {
    return array_keys(self::$data);
  }

  /**
   * Returns an array of Composer package strings, including names and versions.
   *
   * @return string[]
   */
  public static function packageStrings() {
    $packages = [];
    foreach (self::$data as $package_name => $data) {
      if (!empty($data['version'])) {
        $packages[$package_name] = "{$package_name}:{$data['version']}";
        if (!empty($data['submodules'])) {
          foreach ($data['submodules'] as $submodule) {
            $packages["drupal/{$submodule}"] = "drupal/{$submodule}:{$data['version']}";
          }
        }
      }
    }
    return $packages;
  }

}
