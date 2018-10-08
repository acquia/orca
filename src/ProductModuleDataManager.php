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
      'dir' => 'acsf-tools',
      'module' => FALSE,
    ],

    'acquia/drupal-spec-tool' => [
      'version' => '*',
      'dir' => 'drupal-spec-tool',
      'module' => FALSE,
    ],

    'drupal/acquia_commercemanager' => [
      'version' => '*',
      'dir' => 'commerce-manager',
      'module' => 'acm',
      'submodules' => [
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
        // @todo Temporary workaround.
        'pcb',
      ],
    ],

    'drupal/acquia_connector' => [
      'version' => '*',
      'dir' => 'acquia-connector',
      'module' => 'acquia_connector',
      'submodules' => [
        // 'acquia_search',
      ],
    ],

    'drupal/acquia_contenthub' => [
      'version' => '~1.0',
      'dir' => 'content-hub-d8',
      'module' => 'acquia_contenthub',
      'submodules' => [
        'acquia_contenthub_diagnostic',
        'acquia_contenthub_status',
        'acquia_contenthub_subscriber',
      ],
    ],

    'drupal/acquia_lift' => [
      'version' => '*',
      'dir' => 'acquia_lift',
      'module' => 'acquia_lift',
      'submodules' => [
        'acquia_lift_inspector',
      ],
    ],

    'drupal/acquia_purge' => [
      'version' => '*',
      'dir' => 'acquia_purge',
      'module' => 'acquia_purge',
    ],

    'drupal/acsf' => [
      'version' => '*',
      'dir' => 'acsf',
      'module' => 'acsf',
      'submodules' => [
        'acsf_duplication',
        'acsf_sso',
        'acsf_theme',
        'acsf_variables',
      ],
    ],

    'drupal/lightning_api' => [
      'version' => '*',
      'dir' => 'lightning_api',
      'module' => 'lightning_api',
    ],

    'drupal/lightning_core' => [
      'version' => '*',
      'dir' => 'lightning-core',
      'module' => 'lightning_core',
      'submodules' => [
        'lightning_contact_form',
        'lightning_page',
        'lightning_roles',
        'lightning_search',
      ],
    ],

    'drupal/lightning_layout' => [
      'version' => '*',
      'dir' => 'lightning-layout',
      'module' => 'lightning_layout',
      'submodules' => [
        'lightning_landing_page',
      ],
    ],

    'drupal/lightning_media' => [
      'version' => '*',
      'dir' => 'lightning-media',
      'module' => 'lightning_media',
      'submodules' => [
        'lightning_media_audio',
        'lightning_media_bulk_upload',
        'lightning_media_document',
        'lightning_media_image',
        'lightning_media_instagram',
        // 'lightning_media_slideshow',
        'lightning_media_twitter',
        'lightning_media_video',
      ],
    ],

    'drupal/lightning_workflow' => [
      'version' => '*',
      'dir' => 'lightning-workflow',
      'module' => 'lightning_workflow',
      'submodules' => [
        'lightning_scheduler',
      ],
    ],

    'drupal/media_acquiadam' => [
      'version' => '*',
      'dir' => 'media_acquiadam',
      'module' => 'media_acquiadam',
      'submodules' => [
        'lightning_acquiadam',
        // 'media_acquiadam_example',
        'media_acquiadam_report',
      ],
    ],
  ];

  /**
   * Returns the directory name for the given package.
   *
   * @param string $package
   *   A package name, e.g., drupal/example.
   *
   * @return string
   */
  public static function dir($package) {
    if (!array_key_exists($package, self::$data)) {
      throw new \InvalidArgumentException(sprintf('No such package: "%s"', $package));
    }

    return self::$data[$package]['dir'];
  }

  /**
   * Gets the main module name for a given package.
   *
   * @param string $package
   *   A package name, e.g., drupal/example.
   *
   * @return string|false
   *   A module name, if available, or FALSE if not.
   */
  public static function moduleName($package) {
    if (empty(self::$data[$package]['module'])) {
      return FALSE;
    }

    return self::$data[$package]['module'];
  }

  /**
   * Returns an array of Drupal module names, optionally limited by package.
   *
   * @param string|false $package
   *   A package name to limit to, or FALSE for all.
   *
   * @return string[]
   */
  public static function moduleNamePlural($package = FALSE) {
    $modules = [];
    foreach (self::$data as $package_name => $data) {
      if ($package && $package !== $package_name) {
        continue;
      }

      if (!empty($data['module'])) {
        $modules[] = $data['module'];
        if (!empty($data['submodules'])) {
          foreach ($data['submodules'] as $submodule) {
            $modules[] = $submodule;
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
  public static function packageStringPlural() {
    $packages = [];
    foreach (self::$data as $package_name => $datum) {
      if (!empty($datum['version'])) {
        $packages[$package_name] = "{$package_name}:{$datum['version']}";
        if (!empty($datum['submodules'])) {
          foreach ($datum['submodules'] as $submodule) {
            $packages["drupal/{$submodule}"] = "drupal/{$submodule}:{$datum['version']}";
          }
        }
      }
    }
    return $packages;
  }

  /**
   * Returns an array of Composer project names.
   *
   * That is, the part of the package strings after the forward slash (/).
   *
   * @param string|false $package
   *   A package name to limit to, or FALSE for all.
   *
   * @return string[]
   */
  public static function projectNamePlural($package = FALSE) {
    $names = [];
    $data = ($package) ? [$package => []] : self::$data;
    foreach ($data as $package_name => $datum) {
      $names[] = substr($package_name, strpos($package_name, '/') + 1);
    }
    return $names;
  }

}
